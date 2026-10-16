<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class FeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fee::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'class_id'   => ['required', 'integer', 'exists:my_classes,id'],
            'owing'      => ['nullable', 'integer', 'min:0', 'max:8388607'],
            'paid'       => ['required', 'integer', 'min:0', 'max:8388607'],
            'term'       => ['required', 'in:1,2,3'],
            'session'    => ['required', 'digits:4', 'integer'],
            'date_paid'  => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Guard against a duplicate row hitting the composite primary key
            // (student_id, class_id, term, date_paid) with a raw DB error instead
            // of a clean validation message.
            if ($this->filled(['student_id', 'class_id', 'term'])) {
                $exists = DB::table('fees')
                    ->where('student_id', $this->student_id)
                    ->where('class_id', $this->class_id)
                    ->where('term', $this->term)
                    ->where('date_paid', $this->date_paid ?? now()->toDateString())
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('date_paid', 'A fee record already exists for this student, class, term and date.');
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
 * - date_paid (defaults to now() in the migration; left nullable here so the
 *   controller can stamp it, but still accepted if a legitimate backdated entry
 *   is needed — e.g. recording a payment made in person yesterday)
 *
 * Note: `owing` and `paid` as mediumInteger — same caution as with Fees on the
 * users table: these look like currency amounts stored as plain integers
 * (presumably kobo/cents). Worth confirming that's intentional rather than a
 * loss of decimal precision on a real currency column.
 */