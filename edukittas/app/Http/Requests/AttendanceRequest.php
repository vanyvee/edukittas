<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Attendance::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'school_id' => ['sometimes', 'integer', 'exists:my_schools,id'], // usually injected from auth context
            'session'   => ['required', 'digits:4', 'integer'],
            'term'      => ['required', 'in:1,2,3'],
            'date'      => ['required', 'date', 'before_or_equal:today'],

            // Raw student-id list submitted by the frontend; concatenated into
            // sid_date_cid server-side rather than trusted as a pre-built string.
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}

/*
 * Server-controlled / not from frontend:
 * - school_id (usually the authenticated user's school — validated with `sometimes`
 *   only to allow for a super-admin marking attendance for another school)
 * - sid_date_cid (this composite string column should be BUILT server-side from
 *   student_ids + date + class, never accepted as a raw 255-char string straight
 *   from the client — trusting it as-is is a data-integrity risk)
 *
 * Note: the composite primary key (date, session, term, school_id) means this table
 * can hold only ONE row per school per day/term. If you need per-class or per-student
 * granularity in attendance, that needs a different table shape or an expanded key.
 */