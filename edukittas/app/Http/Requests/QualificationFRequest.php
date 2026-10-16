<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class QualificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Qualification::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'qname' => $this->filled('qname') ? trim($this->qname) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'qname' => ['required', 'string', 'max:11', 'unique:qualifications,qname'],
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
 * - id (tinyIncrements, auto — this caps the table at 255 rows total, worth
 *   confirming that ceiling is actually intentional for a qualifications list)
 */