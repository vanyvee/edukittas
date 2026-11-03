<?php

namespace App\Policies;

use App\Models\CbtAttempt;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may touch a particular attempt. Only the person who took it. A student's attempt belongs to
 * their enrollment, which belongs to one school, so an attempt from another school can never match.
 */
class CbtAttemptPolicy
{
    /**
     * WHAT IT DOES: Does this attempt belong to the person asking?
     *
     * USED WHEN: Autosave, submit and result (can:own,attempt).
     *
     * GIVES BACK: true or false.
     */
    public function own($user, CbtAttempt $attempt): bool
    {
        $actor = $user instanceof Student ? $user->currentEnrollment() : ($user instanceof User ? $user : null);

        return $actor instanceof Model && $attempt->isOwnedBy($actor);
    }
}
