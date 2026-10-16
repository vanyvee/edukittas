<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class GradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Grade::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'school_id'  => ['sometimes', 'integer', 'exists:my_schools,id'], // usually injected from auth context
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'class_id'   => ['required', 'integer', 'exists:my_classes,id'],
            'sub_type'   => ['required', 'in:s,a,b'],
            'term'       => ['required', 'in:1,2,3'],
            'session'    => ['required', 'digits:4', 'integer'],

            // Adjust the max per your school's actual grading scheme (e.g. 20/20/60
            // split is common) — 0-100 here is a safe generic default per component.
            'first_test'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'second_test' => ['nullable', 'integer', 'min:0', 'max:100'],
            'exam'        => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Composite primary key is (school_id, subject_id, class_id, student_id,
            // term, session) — catch a duplicate entry cleanly instead of a raw DB error.
            if ($this->filled(['school_id', 'subject_id', 'class_id', 'student_id', 'term', 'session'])) {
                $exists = DB::table('grades')
                    ->where('school_id', $this->school_id)
                    ->where('subject_id', $this->subject_id)
                    ->where('class_id', $this->class_id)
                    ->where('student_id', $this->student_id)
                    ->where('term', $this->term)
                    ->where('session', $this->session)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('student_id', 'A grade record already exists for this student, subject, class, term and session. Use the update endpoint instead.');
                }
            }
        });
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
 * - compiled (flips to true once a separate "compile results" process has run —
 *   this should never be settable directly on a create/update-scores request,
 *   or a client could mark their own grade as finalized prematurely)
 * - timestamps
 *
 * Migration bugs to fix before this works at all:
 * - `school_id` foreign key targets `schools`, but every other migration in this
 *   project creates the table as `my_schools`. Change to ->on('my_schools').
 * - `subject_id`/`class_id` are unsignedTinyInteger (max 255) here but
 *   mediumIncrements (much larger) on their source tables — widen to
 *   unsignedMediumInteger to avoid a silent ceiling once either table grows.
 */