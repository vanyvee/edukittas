<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Exists for the writes around a school that must succeed or fail together:
 * provisioning a new school along with its first director account, and
 * rolling the academic session/term forward. Everything else about School
 * is plain Eloquent and doesn't need a wrapper here.
 */
class SchoolService extends Service
{
    /**
     * Create a school and register its first user (director, ictAdmin, or
     * principal — whoever is registering it) in one transaction, attaching
     * them to one or more departments. If any write fails, nothing persists.
     *
     * $data is the validated payload from RegisterSchoolRequest:
     *   ['school' => [...], 'user' => [...], 'department_ids' => [...]]
     * Nested rather than flat specifically so school.phone/email/address/dob/
     * lg_id/status and user.phone/email/address/dob/lg_id/status never collide —
     * both tables have columns with these exact names, and a flat merged
     * payload would let one silently overwrite the other before either insert.
     */
    public function register(array $data): array
    {
        return $this->transaction(function () use ($data) {
            $school = School::create($data['school']);
            $user = User::create($data['user']);

            // One attach() call for all departments, each carrying the same school_id pivot value — a single query instead of one insert per department.
            $user->departments()->attach(
                collect($data['department_ids'])->mapWithKeys(fn ($id) => [
                    $id => ['school_id' => $school->id],])->all()
            );

            return ['school' => $school, 'user' => $user];
        });
    }

    /**
     * Toggle a school's activation state. Kept as its own method rather than
     * a raw ->update(['status' => ...]) call so activation/suspension stays
     * a deliberate, auditable action rather than an incidental field write.
     */
    public function setStatus(School $school, bool $active): School
    {
        $school->update(['status' => $active]);

        return $school->fresh();
    }

    /**
     * Advance the school to a new term, or roll into a new session when
     * moving past term 3. This is the one place term/session should change —
     * doing it here instead of a raw update() means the rollover rule
     * (term 3 -> term 1 of next session) can't be bypassed or done
     * inconsistently by different callers.
     */
    public function advanceTerm(School $school): School
    {
        return $this->transaction(function () use ($school) {
            if ($school->current_term >= 3) {
                $school->current_term = 1;
                $school->current_session = $school->current_session + 1;
            } else {
                $school->current_term = $school->current_term + 1;
            }

            $school->save();

            return $school;
        });
    }

    /**
     * Replace the school's logo/passport image path after a file has
     * already been stored by the controller. This service never touches
     * the uploaded file itself — only the resulting path string — keeping
     * storage concerns out of the service layer.
     */
    public function updateBranding(School $school, ?string $logoPath = null, ?string $pixPath = null): School
    {
        $school->update(array_filter([
            'logo' => $logoPath,
            'pix'  => $pixPath,
        ], fn ($value) => $value !== null));

        return $school->fresh();
    }
}