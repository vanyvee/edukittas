<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(protected UserService $users)
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
}