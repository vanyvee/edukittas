<?php

namespace App\Services;


use App\Models\School;
use App\Models\User;
use App\Notifications\StaffAddedToSchoolNotification;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Support\Arr;
use Throwable;
/**
 * Exists for the one thing a plain User::create() can't do safely:
 * creating a staff account and putting them in a school are two writes
 * that must succeed or fail together. This is the ONLY place a user is
 * registered: a teacher is just a user with role 'teacher'. Everything else about User is
 * plain Eloquent and doesn't need a wrapper here.
 *
 * A User is a staff member, so staff registration lives here too:
 * registerStaff() (two-step: register, then the person activates from an
 * emailed link), sendInvitation(), resendInvitation(), sendAddedNotice(), activate().
 */
class UserService extends Service
{

public function register(array $data, int $schoolId): array
{
    return $this->transaction(function () use ($data, $schoolId)
     {

        $user = $this->findExistingUser($data);

        if ($user) {

            if ($user->departments()->wherePivot('school_id', $schoolId)->exists()) 
            {
                 // Already belongs to this school
                return ['status' => 'already_exists'];
            }
            session(['user_registration_confirmation' => $user->uuid]);
            return [
                 'status' => 'confirmation_required',
                'id' => $user->uuid,
                'user' => ['pix' => $user->pix, 'surname' => $user->surname,
                 'first_name' => $user->first_name, 'other_name' => $user->other_name
                ],
            ];

        }

        $user = User::create($data);
        $user->departments()->attach($data['department_id'], ['school_id' => $schoolId]);
        return [ 'status' => 'created',];
    });
}   









    /**
     * WHAT IT DOES: Creates the staff account and links it to 1 or 2 departments of this school, in one
     *    transaction, then emails them. A person already known by phone or email is never changed. Without
     *    $confirmed the answer asks for confirmation; with it they are only linked to whichever of the chosen
     *    departments they do not hold here yet. Refused only when they already hold all of them.
     *    Email: a brand-new account gets the activation link; an existing person new to this school gets a
     *    "you have been added" notice (or the activation link if they never activated); extra departments at
     *    a school they already work in send nothing.
     *
     * USED WHEN: POST /staff. $data['department_ids'] is a list of 1 or 2 department ids.
     *
     * GIVES BACK: ['status' => 'created', 'id' => uuid, 'invited' => bool]
     *    | ['status' => 'added', 'name' => ..., 'emailed' => true|false|null]
     *    | ['status' => 'confirmation_required', 'user' => preview, 'in_school' => bool]
     *    | ['status' => 'already_exists']
     */
    public function registerStaff(array $data, int $schoolId, bool $confirmed = false): array
    {
        $wanted = array_values(array_unique(array_map('intval', $data['department_ids'])));

        $result = $this->transaction(function () use ($data, $schoolId, $confirmed, $wanted) {
            $existing = User::where('phone', $data['phone'])->orWhere('email', $data['email'])->first();

            if ($existing) {
                $have    = $existing->departments()->wherePivot('school_id', $schoolId)->pluck('departments.id')->all();
                $missing = array_values(array_diff($wanted, $have));

                if (! $missing) {
                    return ['status' => 'already_exists'];
                }
                if (! $confirmed) {
                    return [
                        'status'    => 'confirmation_required',
                        'user'      => $existing->only(['surname', 'first_name', 'other_name', 'role']),
                        'in_school' => (bool) $have,
                    ];
                }

                $this->linkToSchool($existing, $missing, $schoolId);

                return ['status' => 'added', 'user' => $existing, 'new_to_school' => ! $have];
            }

            $user = User::create(Arr::except($data, ['department_ids']) + ['status' => 0]);
            $this->linkToSchool($user, $wanted, $schoolId);

            return ['status' => 'created', 'user' => $user];
        });

        // Emails go out after the commit: a mail server problem must not undo a registration that did succeed.
        if ($result['status'] === 'created') {
            return ['status' => 'created', 'id' => $result['user']->uuid, 'invited' => $this->sendInvitation($result['user'], $schoolId)];
        }

        if ($result['status'] === 'added') {
            $user = $result['user'];
            $emailed = null;

            if ($result['new_to_school']) {
                // no username yet = invited somewhere but never activated: they need the link, not a login reminder
                $emailed = $user->username === null
                    ? $this->sendInvitation($user, $schoolId)
                    : $this->sendAddedNotice($user, $schoolId);
            }

            return ['status' => 'added', 'name' => trim("{$user->surname} {$user->first_name}"), 'emailed' => $emailed];
        }

        return $result;
    }



    /** One row per department in department_users, each stamped with the school. */
    private function linkToSchool(User $user, array $departmentIds, int $schoolId): void
    {
        $user->departments()->attach(array_fill_keys($departmentIds, ['school_id' => $schoolId]));
    }

    /** "You have been added to {school}": for people who can already log in. */
    public function sendAddedNotice(User $user, int $schoolId): bool
    {
        try {
            $user->notify(new StaffAddedToSchoolNotification(School::whereKey($schoolId)->value('name')));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * WHAT IT DOES: Sends (or re-sends) the invitation. Only for someone of this school who has not activated yet.
     * USED WHEN: Right after registering, and POST /staff/{uuid}/invitation when the first email failed or got lost.
     * GIVES BACK: true if the mail was handed to the mail server.
     */
    public function sendInvitation(User $user, int $schoolId): bool
    {
        try {
            $user->notify(new StaffInvitationNotification(School::whereKey($schoolId)->value('name')));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    public function resendInvitation(User $user, int $schoolId): bool
    {
        abort_unless($user->departments()->wherePivot('school_id', $schoolId)->exists(), 404);
        abort_if($user->status || $user->username !== null, 409, 'This person has already activated their account.');

        return $this->sendInvitation($user, $schoolId);
    }

    /**
     * WHAT IT DOES: The invited person sets their username and password; the account becomes active.
     *    The row is locked first so two clicks cannot both succeed. The password goes through the User
     *    model's `hashed` cast. Opening the link proves the email address, so it is marked verified.
     * USED WHEN: POST /staff/activate/{uuid}, from the activation page.
     * GIVES BACK: Nothing. Throws 409 if the account was already activated.
     */
    public function activate(User $user, string $username, string $password): void
    {
        $this->transaction(function () use ($user, $username, $password) {
            $fresh = User::whereKey($user->id)->lockForUpdate()->first();

            abort_if($fresh->status || $fresh->username !== null, 409, 'This account has already been activated.');

            $fresh->forceFill([
                'username'          => $username,
                'password'          => $password,
                'status'            => 1,
                'email_verified_at' => now(),
            ])->save();
        });
    }












public function assignExistingUserDepartment( string $uuid, int $schoolId, int $departmentId): User {
    abort_unless(session('user_registration_confirmation') === $uuid, 403 );
    $user = User::where('uuid', $uuid)->firstOrFail();
    $user->departments()->attach($departmentId, ['school_id' => $schoolId] );
    session()->forget('user_registration_confirmation');
    return $user;
}



     private function findExistingUser(array $data): ?User
    {
        return User::query()
            ->when(!empty($data['nin']), fn (Builder $query) =>   $query->where('nin', $data['nin'])
            )
            ->when(!empty($data['phone']), fn (Builder $query) =>        $query->orWhere('phone', $data['phone'])
            )
            ->first();
    }
}