<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class StudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Adjust to your actual permission check (policy/gate/role).
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
           // && $this->user()->can('create', \App\Models\Student::class);
    }

    /**
     * Normalize input before validation runs.
     * Trims strings, fixes casing on enums, strips non-digits from phone/nin.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'surname'    => $this->filled('surname') ? trim($this->surname) : null,
            'first_name' => $this->filled('first_name') ? trim($this->first_name) : null,
            'other_name' => $this->filled('other_name') ? trim($this->other_name) : null,
            'sex'        => $this->filled('sex') ? strtolower(trim($this->sex)) : null,
            'arm'        => $this->filled('arm') ? strtoupper(trim($this->arm)) : null,
            'phone'      => $this->filled('phone') ? preg_replace('/\D/', '', $this->phone) : null,
            'nin'        => $this->filled('nin') ? preg_replace('/\D/', '', $this->nin) : null,
            'email'      => $this->filled('email') ? strtolower(trim($this->email)) : null,
        ]);
    }

    /**
     * Rules cover ONLY fields that genuinely come from the frontend.
     * See the bottom of this file for fields deliberately excluded and why.
     */
    public function rules(): array
    {
        return [
            // Identity
            'surname'        => ['required', 'string', 'max:20'],
            'first_name'     => ['required', 'string', 'max:20'],
            'other_name'     => ['nullable', 'string', 'max:20'],
            'username'       => ['required', 'string', 'max:20', 'alpha_dash', 'unique:students,username'],
            'dob'            => ['required', 'date', 'before:today', 'after:1980-01-01'],
            'sex'            => ['required', 'in:f,m'],
            'nin'            => ['nullable', 'digits:13'],
            'phone'          => ['required', 'digits_between:10,11'],
            'email'          => ['nullable', 'email', 'max:20'],

            // Academic / relational
            'class_id'       => ['required', 'integer', 'exists:my_classes,id'],
            'school_id'      => ['sometimes', 'integer', 'exists:my_schools,id'], // usually injected server-side, see note
            'guardian_id'    => ['required', 'integer', 'exists:guardians,id'],
            'lg_id'          => ['required', 'integer', 'exists:lgas,id'],
            'sub_type'       => ['required', 'in:s,a,b'],
            'arm'            => ['nullable', 'in:0,A,B,C,D,E,F,G,H,I,J'],
            'Fees'           => ['required', 'integer', 'min:0', 'max:8388607'], // mediumint signed upper bound// Dates
            'addmission_date'  => ['nullable', 'date'], // falls back to today() server-side if omitted
            'graduation_date'  => ['nullable', 'date', 'after_or_equal:addmission_date'],

            // File uploads — these are NOT the same as the stored `pix`/`passport` string columns.
            // The column stores a filename/path; the request receives an actual file.
            'pix_file'       => ['nullable', 'image', 'max:2048'],      // max in KB
            'passport_file'  => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * Cross-field checks that a simple rule string can't express cleanly.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('graduation_date') && $this->filled('addmission_date')) {
                if (strtotime($this->graduation_date) < strtotime($this->addmission_date)) {
                    $validator->errors()->add('graduation_date', 'Graduation date cannot be before admission date.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'username.unique'   => 'That username is already taken.',
            'nin.digits'        => 'NIN must be exactly 11 digits.',
            'phone.digits_between' => 'Phone number must be 10 or 11 digits.',
            'class_id.exists'   => 'Selected class does not exist.',
            'guardian_id.exists' => 'Selected guardian does not exist.',
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