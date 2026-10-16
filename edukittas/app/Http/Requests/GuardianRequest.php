<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Guardian::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'surname'    => $this->filled('surname') ? trim($this->surname) : null,
            'other_name' => $this->filled('other_name') ? trim($this->other_name) : null,
            'sex'        => $this->filled('sex') ? strtolower(trim($this->sex)) : null,
            'phone'      => $this->filled('phone') ? preg_replace('/\D/', '', $this->phone) : null,
            'email'      => $this->filled('email') ? strtolower(trim($this->email)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'surname'    => ['required', 'string', 'max:20'],
            'other_name' => ['nullable', 'string', 'max:20'],
            'username'   => ['required', 'string', 'max:20', 'alpha_dash', 'unique:guardians,username'],
            'sex'        => ['required', 'in:f,m'],
            'phone'      => ['required', 'digits_between:10,11'],
            'email'      => ['nullable', 'email', 'max:20'],

            'passport_file' => ['nullable', 'image', 'max:2048'],
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
 * - id, uuid (generated server-side)
 * - passport (set from uploaded file path, not raw client string)
 * - password (defaults to '12345' — set an explicit hashed value server-side,
 *   or better, trigger a real invite/reset-link flow instead of relying on this)
 * - timestamps
 */