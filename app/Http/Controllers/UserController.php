<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserController extends Controller
{
     use Queueable;
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $users = User::whereHas('schools', fn ($q) => $q->where('schools.id', session('school_id')))
            ->when($request->role, fn ($q, $role) => $q->role($role))
            ->get();

        return response()->json($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $departmentId = $data['department_id'];
        unset($data['department_id']);

        if ($existing = $this->users->findDuplicatePerson($data['phone'] ?? null, $data['nin'] ?? null)) {
            return response()->json([
                'message'       => 'A user matching this phone/NIN already exists.',
                'action'        => 'assign_to_department',
                'existing_user' => ['uuid' => $existing->uuid, 'full_name' => $existing->full_name],
            ], 409);
        }

        $user = $this->users->register($data, session('school_id'), $departmentId);

        return response()->json($user, 201);
    }

    public function assign(AssignUserRequest $request, User $user): JsonResponse
    {
        $attached = $this->users->assignToSchool($user, session('school_id'), $request->validated('department_id'));

        if (! $attached) {
            return response()->json(['message' => "{$user->username} is already in that department at this school."], 422);
        }

        return response()->json($user->load('departments'));
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('departments', 'schools'));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(status: 204);
    }









  

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // The link opens the activation page in the app; the page sends this same signature back with the password.
        $url = URL::temporarySignedRoute('staff.activate.show', now()->addDays(7), ['uuid' => $notifiable->uuid]);
        $school = $this->schoolName ?: 'Your school';

        return (new MailMessage)
            ->subject('Activate your staff account')
            ->greeting("Hello {$notifiable->first_name},")
            ->line("{$school} has registered you as a staff member.")
            ->action('Choose your username and password', $url)
            ->line('This link expires in 7 days and can be used once. If you were not expecting it, you can ignore this email.');
    }
    /**
     * WHAT IT DOES: Sets the username and password and turns the account active.
     * USED WHEN: POST /staff/activate/{user} from the activation page, with the emailed link's expires and signature.
     * GIVES BACK: { username }. 403 if the link is invalid or expired, 409 if already activated.
     */
    public function store(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->users->activate($user, $data['username'], $data['password']);

        return response()->json(['username' => $data['username']]);
    }

    /**
     * WHAT IT DOES: The lists the registration form's dropdowns need.
     * USED WHEN: GET /staff/options.
     * GIVES BACK: { lgas, departments, qualifications }
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'lgas'           => DB::table('lgas')->orderBy('state')->orderBy('name')->get(['id', 'name', 'state']),
            'departments'    => Department::orderBy('name')->get(['id', 'name']),
            'qualifications' => DB::table('qualifications')->orderBy('id')->pluck('qname'),
        ]);
    }

    /**
     * WHAT IT DOES: Saves the passport photo, registers the staff member and emails them a signed link.
     *    If the person is already known (phone or email) nothing is created: the answer is
     *    'confirmation_required' and the front end asks the admin. When the admin agrees, the front end
     *    posts the same form again with confirmed=1 and the person is added to this school.
     *
     * USED WHEN: POST /staff (multipart).
     *
     * GIVES BACK: 201 { status: 'created', id, invited }, or 200 with status 'confirmation_required'
     *    (+ preview), 'added' (+ name) or 'already_exists'. The photo is removed again unless a
     *    new account was created.
     */
    public function store(StaffRequest $request): JsonResponse
    {
        $rejected = [];
        $passport = FileUploadHelper::uploadOne($request, 'passport', 'users/passports', ['image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'maxSizeMB'  => 2,
            'dimensions' => ['min' => [150, 150]],
        ]], $rejected);

        if ($rejected) {
            return response()->json(['message' => 'The photo was not accepted.', 'errors' => ['passport' => $rejected]], 422);
        }

        $data = Arr::except($request->validated(), ['passport', 'confirmed']);
        $data['passport'] = $passport;

        try {
            $result = $this->users->registerStaff($data, Helper::schoolId(), $request->boolean('confirmed'));
        } catch (Throwable $e) {
            $this->forget($passport);
            Log::error('Staff registration failed: ' . $e->getMessage());

            return response()->json(['message' => 'We could not register this staff member. Please try again.'], 500);
        }

        if ($result['status'] !== 'created') {
            $this->forget($passport);
        }

        return response()->json($result, $result['status'] === 'created' ? 201 : 200);
    }

    /**
     * WHAT IT DOES: Sends the invitation again to someone of this school who has not activated yet.
     * USED WHEN: POST /staff/{uuid}/invitation, when the first email failed or was lost.
     * GIVES BACK: { invited: bool }
     */
    public function resend(User $user): JsonResponse
    {
        return response()->json(['invited' => $this->users->resendInvitation($user, Helper::schoolId())]);
    }

    private function forget(?string $path): void
    {
        if ($path) Storage::disk('public')->delete($path);
    }
}