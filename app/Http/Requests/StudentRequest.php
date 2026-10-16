<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $guardian = $this->input('guardian', []);
        $student  = $this->input('student', []);

        $this->merge([
            'guardian' => array_merge($guardian, [
                'username' => isset($guardian['username']) ? strtolower(trim($guardian['username'])) : null,
                'email'    => isset($guardian['email']) ? strtolower(trim($guardian['email'])) : null,
                'phone'    => isset($guardian['phone']) ? preg_replace('/\D/', '', $guardian['phone']) : null,
            ]),
            'student' => array_merge($student, [
                'surname'    => isset($student['surname']) ? trim($student['surname']) : null,
                'first_name' => isset($student['first_name']) ? trim($student['first_name']) : null,
                'sex'        => isset($student['sex']) ? strtolower(trim($student['sex'])) : null,
                'phone'      => isset($student['phone']) ? preg_replace('/\D/', '', $student['phone']) : null,
                'nin'        => isset($student['nin']) ? preg_replace('/\D/', '', $student['nin']) : null,
                'email'      => isset($student['email']) ? strtolower(trim($student['email'])) : null,
            ]),
        ]);
    }

    public function rules(): array
    {
        $confirmedGuardianId = $this->input('guardian.confirmed_guardian_id');

        return [
            // Guardian — either a fresh registration, or a confirmed match
            // (optionally updating contact details on that existing row).
            'guardian'                        => ['required', 'array'],
            'guardian.confirmed_guardian_id'  => ['nullable', 'integer', 'exists:guardians,id'],
            'guardian.update_contact'         => ['nullable', 'boolean'],
            'guardian.surname'                => ['required_without:guardian.confirmed_guardian_id', 'string', 'max:20'],
            'guardian.other_name'             => ['nullable', 'string', 'max:20'],
            'guardian.sex'                    => ['required_without:guardian.confirmed_guardian_id', 'in:f,m'],
            'guardian.phone'                  => ['required_without:guardian.confirmed_guardian_id', 'digits_between:10,11'],
            'guardian.username'               => [
                'required_without:guardian.confirmed_guardian_id',
                'nullable', 'string', 'max:20', 'alpha_dash',
                Rule::unique('guardians', 'username')->ignore($confirmedGuardianId),
            ],
            'guardian.email'                  => [
                'nullable', 'email', 'max:20',
                Rule::unique('guardians', 'email')->ignore($confirmedGuardianId),
            ],

            // Student identity. No unique:students,username rule here —
            // see withValidator() below for why, and how it's still enforced.
            'student'             => ['required', 'array'],
            'student.surname'     => ['required', 'string', 'max:20'],
            'student.first_name'  => ['required', 'string', 'max:20'],
            'student.other_name'  => ['nullable', 'string', 'max:20'],
            'student.username'    => ['required', 'string', 'max:20', 'alpha_dash'],
            'student.dob'         => ['required', 'date', 'before:today'],
            'student.sex'         => ['required', 'in:f,m'],
            'student.lg_id'       => ['required', 'integer', 'exists:lgas,id'],
            'student.nin'         => ['nullable', 'digits:13'],
            'student.phone'       => ['required', 'digits_between:10,11'],
            'student.email'       => ['nullable', 'email', 'max:20'],
            'student.passport_file' => ['nullable', 'image', 'max:2048'],

            // Enrollment
            'enrollment'                => ['required', 'array'],
            'enrollment.class_id'       => ['required', 'integer', 'exists:my_classes,id'],
            'enrollment.school_id'      => ['sometimes', 'integer', 'exists:my_schools,id'], // usually injected from session
            'enrollment.sub_type'       => ['nullable', 'in:s,a,b'],
            'enrollment.arm'            => ['nullable', 'in:0,A,B,C,D,E,F,G,H,I,J'],
            'enrollment.admission_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Mirrors StudentService::findExisting() so the exact same student
     * being re-enrolled can resubmit their own username without tripping
     * a uniqueness error — only a DIFFERENT student taking an already-used
     * username is rejected.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $studentData = $this->input('student', []);

            if (empty($studentData['username'])) {
                return;
            }

            $existing = Student::query()
                ->when(!empty($studentData['nin']), fn ($q) => $q->where('nin', $studentData['nin']))
                ->when(!empty($studentData['phone']), fn ($q) => $q->orWhere('phone', $studentData['phone']))
                ->first();

            $taken = Student::where('username', $studentData['username'])
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->exists();

            if ($taken) {
                $validator->errors()->add('student.username', 'This username is already taken.');
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
 * - guardian.id, guardian.uuid, guardian.password (set later via the claim
 *   link, never collected at registration)
 * - student.id, student.uuid, student.password (system default)
 * - enrollment.id, enrollment.status (defaults active), enrollment.post,
 *   enrollment.fees, timestamps throughout
 */