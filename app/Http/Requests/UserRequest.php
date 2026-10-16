<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'surname'        => $this->filled('surname') ? trim($this->surname) : null,
            'first_name'     => $this->filled('first_name') ? trim($this->first_name) : null,
            'other_name'     => $this->filled('other_name') ? trim($this->other_name) : null,
            'sex'            => $this->filled('sex') ? strtolower(trim($this->sex)) : null,
            'phone'          => $this->filled('phone') ? preg_replace('/\D/', '', $this->phone) : null,
            'recovery_phone' => $this->filled('recovery_phone') ? preg_replace('/\D/', '', $this->recovery_phone) : null,
            'email'          => $this->filled('email') ? strtolower(trim($this->email)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'surname'        => ['required', 'string', 'max:20'],
            'first_name'     => ['required', 'string', 'max:20'],
            'other_name'     => ['nullable', 'string', 'max:20'],
            'username'       => ['required', 'string', 'max:20', 'alpha_dash', 'unique:users,username'],
            'role'           => ['required', 'in:director,ictAdmin,principal,vice principal,bursar,teacher,security,other'],
            'salary'         => ['nullable', 'string', 'max:7'],
            'account_number' => ['nullable', 'string', 'max:10'],
            'bank_name'      => ['nullable', 'string', 'max:20'],
            'sex'            => ['required', 'in:m,f'],
            'dob'            => ['required', 'date', 'before:today'],
            'lg_id'          => ['required', 'integer', 'exists:lgas,id'],
            //'form_teacher'   => ['nullable', 'string', 'max:15'],
            'qualification'  => ['required', 'string', 'max:10', 'exists:qualifications,qname'],
            'phone'          => ['nullable', 'digits_between:10,11'],
            'email'          => ['nullable', 'email', 'max:50', 'unique:users,email'],
            'recovery_phone' => ['nullable', 'digits_between:10,11'],
            'address'        => ['required', 'string'],

            'passport_file'  => ['nullable', 'image', 'max:2048'],
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
 * - id, uuid
 * - status (defaults to active — activation/suspension is an admin action, not
 *   something the creation form itself should set)
 * - passport (set from uploaded file path, not a raw client string)
 * - employed_on (defaults to NOW() at the DB level — leave it, don't accept a
 *   client-supplied hire date unless backdating is a genuine business need)
 * - password (defaults to the placeholder '55555' — replace with a real hashed
 *   temp password generated server-side, or trigger an invite/reset-link flow)
 * - email_verified_at, timestamps
 */