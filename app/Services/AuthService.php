<?php

namespace App\Services;

class AuthService extends Service
{
    /** Switch the active school for the logged-in user, after checking real membership. */
    public function switchSchool(int $schoolId): void
    {
        $user = auth()->user();

        $belongs = $user->schools()->where('schools.id', $schoolId)->exists();

        if (! $belongs) {
            throw new \DomainException('You do not belong to that school.');
        }

        session(['school_id' => $schoolId]);
    }
}