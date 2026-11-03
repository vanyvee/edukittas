<?php

namespace App\Policies;

use App\Models\Cbt;
use App\Models\Student;
use App\Models\User;
use App\Support\Helper;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may touch a particular CBT. Laravel finds this class by its name (Cbt -> CbtPolicy).
 *
 * $user is whoever is logged in: a Student or a User (staff). A guardian is refused everywhere here.
 * A CBT's host is an Enrollment (when a student set it up) or a User (staff), so the logged-in
 * person is first turned into that same "actor" before comparing.
 *
 * Every record rule starts with the school check, so a CBT id from another school is refused
 * even when the id is guessed correctly.
 *
 * Business rules (is it open, is this person in its audience, no retakes) stay in CbtService.
 */
class CbtPolicy
{
    /**
     * WHAT IT DOES: May this person see their list of CBTs?
     *
     * USED WHEN: GET /cbts (can:viewAny,Cbt).
     *
     * GIVES BACK: true or false.
     */
    public function viewAny($user): bool
    {
        return $this->isPerson($user);
    }

    /**
     * WHAT IT DOES: May this person set up a new CBT?
     *
     * USED WHEN: POST /cbts (can:create,Cbt).
     *
     * GIVES BACK: true or false.
     */
    public function create($user): bool
    {
        return $this->isPerson($user);
    }

    /**
     * WHAT IT DOES: May this person open this CBT for editing, answer key included? Only the person
     *    who set it up, and only inside the school it belongs to.
     *
     * USED WHEN: GET /cbts/{cbt} (can:view,cbt).
     *
     * GIVES BACK: true or false.
     */
    public function view($user, Cbt $cbt): bool
    {
        return $this->inSchool($cbt) && $this->isHost($user, $cbt);
    }

    /**
     * WHAT IT DOES: May this person change this CBT, or copy its questions to the bank? Same rule
     *    as view.
     *
     * USED WHEN: PUT /cbts/{cbt} and POST /cbts/{cbt}/save-to-bank (can:update,cbt).
     *
     * GIVES BACK: true or false.
     */
    public function update($user, Cbt $cbt): bool
    {
        return $this->view($user, $cbt);
    }

    /**
     * WHAT IT DOES: May this person delete this CBT? Same rule as view (the service still refuses
     *    while someone is taking it).
     *
     * USED WHEN: DELETE /cbts/{cbt} (can:delete,cbt).
     *
     * GIVES BACK: true or false.
     */
    public function delete($user, Cbt $cbt): bool
    {
        return $this->view($user, $cbt);
    }

    /**
     * WHAT IT DOES: May this person start this CBT? It must belong to their school. Whether they are
     *    in its audience and whether it is open is decided by CbtService.
     *
     * USED WHEN: POST /cbts/{cbt}/attempts (can:take,cbt).
     *
     * GIVES BACK: true or false.
     */
    public function take($user, Cbt $cbt): bool
    {
        return $this->isPerson($user) && $this->inSchool($cbt);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * WHAT IT DOES: Is this a staff member or a student? Guardians and anyone else are refused.
     *
     * USED WHEN: Inside the abilities above.
     *
     * GIVES BACK: true or false.
     */
    private function isPerson($user): bool
    {
        return $user instanceof User || $user instanceof Student;
    }

    /**
     * WHAT IT DOES: Does this CBT belong to the school that is active in the session?
     *
     * USED WHEN: Inside view and take, as the first check.
     *
     * GIVES BACK: true or false.
     */
    private function inSchool(Cbt $cbt): bool
    {
        return (int) $cbt->school_id === Helper::currentSchoolId();
    }

    /**
     * WHAT IT DOES: Is this person the one who set the CBT up? A student acts through their current
     *    enrollment; staff act as themselves.
     *
     * USED WHEN: Inside view.
     *
     * GIVES BACK: true or false.
     */
    private function isHost($user, Cbt $cbt): bool
    {
        $actor = $this->actor($user);

        return $actor !== null
            && $cbt->host_type === $actor->getMorphClass()
            && (int) $cbt->host_id === (int) $actor->getKey();
    }

    /**
     * WHAT IT DOES: Turns the logged-in person into the model that CBTs and attempts are tied to.
     *
     * USED WHEN: Inside isHost.
     *
     * GIVES BACK: The enrollment (student), the user (staff), or null.
     */
    private function actor($user): ?Model
    {
        if ($user instanceof Student) {
            return $user->currentEnrollment();
        }

        return $user instanceof User ? $user : null;
    }
}
