<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
/**
 * Exists for the one thing a plain User::create() can't do safely:
 * creating a staff account and putting them in a school are two writes
 * that must succeed or fail together. Everything else about User is
 * plain Eloquent and doesn't need a wrapper here.
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