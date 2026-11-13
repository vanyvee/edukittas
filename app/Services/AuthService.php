<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Supports\Helper;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Everything about logging in and out. Four kinds of login, one guard each. The first character of
 * the username says which:
 *   @  staff, teachers and directors  (web, User)
 *   %  guardians                      (guardian, Guardian)
 *   &  platform admins                (admin, Admin)
 *   no symbol: students               (student, Student), the default
 *
 * Where each person's school comes from (the person tables themselves carry no school):
 *   staff     department_users: one school is set automatically, several are chosen with switchSchool
 *   student   the active enrollment: always exactly one school, set automatically
 *   guardian  no school of their own: they pick a ward with switchWard, and the ward's active
 *             enrollment gives the school
 *   admin     none
 *
 * Only one guard is logged in at a time.
 */
class AuthService extends Service
{
    private const GUARDS = ['student', 'web', 'guardian', 'admin'];

    /** Username prefix => guard and model. Change a symbol here and nowhere else. */
    private const PREFIXES = [
        '@' => ['name' => 'web', 'model' => User::class],
        '%' => ['name' => 'guardian', 'model' => Guardian::class],
        '$' => ['name' => 'admin', 'model' => Admin::class],
    ];

    private const DEFAULT = ['name' => 'student', 'model' => Student::class];

    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 60;

    // ------------------------------------------------------------------
    // Login and logout
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Logs a person in. It picks the guard from the username, refuses after too many
     *    wrong tries, checks the password, and refuses an account that has nowhere to work (a staff
     *    member with no school, a student with no active enrollment, a guardian with no ward)
     *    before anyone is logged in. It then closes any other open guard, wipes the old school
     *    and ward from the session, logs in, and sets the school (or ward) when there is only one
     *    choice. The controller starts the fresh session afterwards.
     *
     * USED WHEN: POST /login.
     *
     * GIVES BACK: The same array as me().
     */
    public function login(string $username, string $password, ?string $ip): array
    {
        $key = Str::lower($username).'|'.$ip;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'username' => ['Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'],
            ]);
        }

        $guard = $this->detectGuard($username);
        $name = $guard['name'];

        // Look the account up ourselves instead of attempt(), so we can refuse it before it is logged in.
        $account = $guard['model']::where('username', $username)->first();

        if (! $account || ! Hash::check($password, $account->password)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);

            throw ValidationException::withMessages(['username' => ['Invalid credentials']]);
        }

        $schools = in_array($name, ['web', 'student'], true) ? $this->schoolsFor($name, $account) : collect();
        $wards = $name === 'guardian' ? $this->wardsFor($account) : collect();

        if (in_array($name, ['web', 'student'], true) && $schools->isEmpty()) {
            throw ValidationException::withMessages([
                'username' => [$name === 'student' ? 'This student has no active enrollment.' : 'This account is not linked to any school.'],
            ]);
        }

        if ($name === 'guardian' && $wards->isEmpty()) {
            throw ValidationException::withMessages(['username' => ['No ward is linked to this account.']]);
        }
        if ($name === 'guardian' && ! $wards->contains(fn ($w) => $w['school'])) {
         throw ValidationException::withMessages(['username' => ['None of your children is currently enrolled in a school.']]);
}

        RateLimiter::clear($key);

        $this->logoutAll();
        Helper::clearContext();

       // Land straight in the app: staff in their first school, a guardian with their first enrolled child.
        if ($schools->isNotEmpty()) {
            Helper::setSchoolId((int) $schools->first()['id']);
        }

        if ($name === 'guardian') {
            $first = $wards->first(fn ($w) => $w['school']);
            Helper::setWardId((int) $first['ward']->getKey());
            Helper::setSchoolId($first['school']['id']);
        }

        Auth::guard($name)->login($account);

        return $this->payload($name, $account);
    }

    /**
     * WHAT IT DOES: Logs out every guard.
     *
     * USED WHEN: POST /logout, and just before a new login so two guards are never open together.
     *
     * GIVES BACK: Nothing.
     */
    public function logoutAll(): void
    {
        foreach (self::GUARDS as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }
    }

    // ------------------------------------------------------------------
    // Who is logged in
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Reports who is logged in, on which guard, which school (and ward) is active,
     *    and what they can still choose.
     *
     * USED WHEN: GET /me, when the app loads or the page is refreshed.
     *
     * GIVES BACK: {guard, user, school_id, schools, needs_school, ward_id, wards, needs_ward};
     *    guard and user are null when nobody is logged in.
     */
    public function me(): array
    {
        $guard = $this->currentGuard();

        if (! $guard) {
            return ['guard' => null, 'user' => null];
        }

        return $this->payload($guard, Auth::guard($guard)->user());
    }

    /**
     * WHAT IT DOES: Finds which guard is currently logged in.
     *
     * USED WHEN: Inside me(), switchSchool() and switchWard().
     *
     * GIVES BACK: The guard name, or null.
     */
    public function currentGuard(): ?string
    {
        foreach (self::GUARDS as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    /**
     * WHAT IT DOES: Builds the answer the front end needs after a login, a refresh or a switch.
     *    `needs_school` is true while a staff member with several schools has not picked one;
     *    `needs_ward` is true while a guardian has not picked a ward.
     *
     * USED WHEN: Inside login() and me().
     *
     * GIVES BACK: {guard, user, school_id, schools, needs_school, ward_id, wards, needs_ward}.
     */
    private function payload(string $guard, Authenticatable $user): array
    {
        $schoolId = Helper::currentSchoolId();
        $wardId = Helper::currentWardId();

        return [
            'guard' => $guard,
            'user' => $user,
            'school_id' => $schoolId,
            'schools' => in_array($guard, ['web', 'student'], true) ? $this->schoolsFor($guard, $user)->all() : [],
            'needs_school' => in_array($guard, ['web', 'student'], true) && $schoolId === null,
            'ward_id' => $wardId,
            'wards' => $guard === 'guardian' ? $this->wardsFor($user)->all() : [],
            'needs_ward' => $guard === 'guardian' && $wardId === null,
        ];
    }

    // ------------------------------------------------------------------
    // Choosing a school (staff) or a ward (guardian)
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Staff only. Makes one of the staff member's schools the active one, after
     *    checking they really belong to it through department_users.
     *
     * USED WHEN: POST /switch-school, after login when a staff member has several schools, or later.
     *
     * GIVES BACK: Nothing (it throws a DomainException, shown as 403, when the check fails).
     */
    public function switchSchool(int $schoolId): void
    {
        if ($this->currentGuard() !== 'web') {
            throw new \DomainException('Only staff can switch school.');
        }

        if (! $this->schoolsFor('web', Auth::guard('web')->user())->contains('id', $schoolId)) {
            throw new \DomainException('You do not belong to that school.');
        }

        Helper::setSchoolId($schoolId);
    }

    /**
     * WHAT IT DOES: Guardian only. Picks which ward to look at. It checks the ward really belongs to
     *    this guardian and is enrolled somewhere, then stores the ward and the ward's school.
     *
     * USED WHEN: POST /switch-ward, after login or whenever the guardian changes child.
     *
     * GIVES BACK: Nothing (it throws a DomainException, shown as 403, when the check fails).
     */
    public function switchWard(int $wardId): void
    {
        if ($this->currentGuard() !== 'guardian') {
            throw new \DomainException('Only guardians can pick a ward.');
        }

        $entry = $this->wardsFor(Auth::guard('guardian')->user())
            ->first(fn ($w) => (int) $w['ward']->getKey() === $wardId);

        if (! $entry) {
            throw new \DomainException('That is not your ward.');
        }

        if (! $entry['school']) {
            throw new \DomainException('That ward has no active enrollment.');
        }

       
        Helper::setWardId($wardId);

        // Same school: nothing else moves. Different school: the session moves with the child.
        if ((int) $entry['school']['id'] !== Helper::currentSchoolId()) {
            Helper::setSchoolId($entry['school']['id']);
        }
       
    }


    
    // ------------------------------------------------------------------
    // Where people belong
    // ------------------------------------------------------------------
private function schoolSummary(object $s): array
{
    return [
        'id' => (int) $s->id, 'name' => $s->name, 'logo' => $s->logo,
        'theme' => $s->theme, 'session' => $s->current_session, 'term' => $s->current_term,
    ];
}
    /**
     * WHAT IT DOES: Lists the schools a staff member or student belongs to. Staff come from the
     *    department_users table; a student comes from their active enrollment. Read with plain
     *    queries because at login no school is active yet and school scopes would stop the request.
     *
     * USED WHEN: Inside login, payload and switchSchool.
     *
     * GIVES BACK: A collection of ['id' => int, 'name' => string].
     */
    public function schoolsFor(string $guard, Authenticatable $user): Collection
    {
        $ids = match ($guard) {
            'web' => DB::table('department_users')->where('user_id', $user->getKey())->distinct()->pluck('school_id'),
            'student' => collect(array_values($this->activeSchoolByStudent([$user->getKey()]))),
            default => collect(),
        };

        return DB::table('schools')->whereIn('id', $ids->unique()->values())->orderBy('name')
        ->get(['id', 'name', 'logo', 'theme', 'current_session', 'current_term'])
        ->map(fn ($s) => $this->schoolSummary($s));
    }

   

    /**
     * WHAT IT DOES: Lists a guardian's wards, each with the school of their active enrollment
     *    (null when the ward is not enrolled anywhere right now).
     *
     * USED WHEN: Inside login, payload and switchWard.
     *
     * GIVES BACK: A collection of ['ward' => the student, 'school' => ['id', 'name'] or null].
     */
    public function wardsFor(Authenticatable $guardian): Collection
    {
        $wards = $guardian->wards()->get();
        $schoolOf = $this->activeSchoolByStudent($wards->pluck('id')->all());
        //$names = DB::table('schools')->whereIn('id', array_values($schoolOf))->pluck('name', 'id');
        $schools = DB::table('schools')->whereIn('id', array_values($schoolOf))
          ->get(['id', 'name', 'logo', 'theme', 'current_session', 'current_term'])->keyBy('id');
       
         return $wards->map(fn ($ward) => [
        'ward' => $ward,
         'school' => isset($schoolOf[$ward->id]) && $schools->has($schoolOf[$ward->id])
        ? $this->schoolSummary($schools[$schoolOf[$ward->id]]) : null,
        ]);
    }


   


    /**
     * WHAT IT DOES: For each given student, the school of their active enrollment. Enrollment is read
     *    without its global scopes on purpose (see schoolsFor).
     *
     * USED WHEN: Inside schoolsFor and wardsFor.
     *
     * GIVES BACK: An array of student id => school id.
     */
    private function activeSchoolByStudent(array $studentIds): array
    {
        return Enrollment::withoutGlobalScopes()
            ->whereIn('student_id', $studentIds)
            ->where('status', '1')
            ->pluck('school_id', 'student_id')
            ->all();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Works out from the first character of the username which guard and model to use.
     *
     * USED WHEN: At the start of login().
     *
     * GIVES BACK: ['name' => guard, 'model' => model class].
     */
    public function detectGuard(string $username): array
    {
        return self::PREFIXES[substr($username, 0, 1)] ?? self::DEFAULT;
    }
}
