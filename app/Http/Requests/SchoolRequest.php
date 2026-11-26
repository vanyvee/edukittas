<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RegisterSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // public sign-up; the route is throttled
    }

    protected function prepareForValidation(): void
    {
        $school = $this->input('school', []);
        $user   = $this->input('user', []);

        $this->merge([
            'school' => array_merge($school, [
                'name'    => isset($school['name']) ? trim($school['name']) : null,
                'email'   => isset($school['email']) ? strtolower(trim($school['email'])) : null,
                'phone'   => isset($school['phone']) ? preg_replace('/\D/', '', $school['phone']) : null,
                'address' => isset($school['address']) ? trim($school['address']) : null,
            ]),
            'user' => array_merge($user, [
                'surname'    => isset($user['surname']) ? trim($user['surname']) : null,
                'first_name' => isset($user['first_name']) ? trim($user['first_name']) : null,
                'other_name' => isset($user['other_name']) ? trim($user['other_name']) : null,
                'username'   => isset($user['username']) ? trim($user['username']) : null,
                'sex'        => isset($user['sex']) ? strtolower(trim($user['sex'])) : null,
                'phone'      => isset($user['phone']) ? preg_replace('/\D/', '', $user['phone']) : null,
                'email'      => isset($user['email']) ? strtolower(trim($user['email'])) : null,
            ]),
        ]);
    }

    public function rules(): array
    {
        $img = 'mimes:jpg,jpeg,png,webp';

        return [
            'school'                  => ['required', 'array'],
            'school.name'             => ['required', 'string', 'max:100'],
            'school.lg_id'            => ['required', 'integer', 'exists:lgas,id'],
            'school.dob'              => ['required', 'date', 'before:today'],
            'school.phone'            => ['required', 'digits_between:10,11'],
            'school.email'            => ['required', 'email', 'max:50', 'unique:schools,email'],   // was my_schools: no such table
            'school.address'          => ['required', 'string', 'max:30'],
            'school.current_session'  => ['required', 'digits:4', 'integer'],
            'school.current_term'     => ['required', 'in:1,2,3'],
            'school.result_template'  => ['nullable', 'in:a,b,c'],
            'school.theme'            => ['nullable', Rule::exists('themes', 'code')->where('is_active', true)],

            'user'             => ['required', 'array'],
            'user.surname'     => ['required', 'string', 'max:20'],
            'user.first_name'  => ['required', 'string', 'max:20'],
            'user.other_name'  => ['nullable', 'string', 'max:20'],
            'user.username'    => ['required', 'string', 'max:20', 'alpha_dash', 'unique:users,username'],
            'user.role'        => ['required', 'in:director,ictAdmin,principal'],
            'user.sex'         => ['required', 'in:m,f'],
            'user.dob'         => ['required', 'date', 'before:today'],
            'user.lg_id'       => ['required', 'integer', 'exists:lgas,id'],
            'user.phone'       => ['required', 'digits_between:10,11'],
            'user.email'       => ['nullable', 'email', 'max:50', 'unique:users,email'],
            'user.address'     => ['required', 'string'],
            // The users table would otherwise default every new account to the plain text '55555'.
            'user.password'    => ['required', 'string', 'min:8', 'confirmed'],

            'department_ids'   => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', 'distinct', 'exists:departments,id'],

            // Images. Logo is required; the rest are optional.
            'logo'       => ['required', 'image', $img, 'max:2048', 'dimensions:min_width=100,min_height=100'],
            'pix'        => ['nullable', 'image', $img, 'max:5120', 'dimensions:min_width=600,min_height=300'],
            'photos'     => ['nullable', 'array', 'max:4'],
            'photos.*'   => ['image', $img, 'max:5120', 'dimensions:min_width=400,min_height=300'],
            'passport'   => ['nullable', 'image', $img, 'max:2048', 'dimensions:min_width=150,min_height=150'],
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
 * Server-controlled / not from frontend (both school and user sides):
 * - school.id, school.pix, school.logo, school.status, school.type,
 *   school.promotion_grade, school.timestamps
 * - user.id, user.uuid, user.status, user.passport, user.employed_on,
 *   user.password, user.email_verified_at, user.timestamps
 *
 * Assumes a `departments` table exists (referenced by the old
 * DepartmentEmployee pivot) — adjust the exists: rule if its real name differs.
 */