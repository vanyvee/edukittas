<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\MyClass::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'  => $this->filled('name') ? trim($this->name) : null,
            'level' => $this->filled('level') ? trim($this->level) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:15', 'unique:my_classes,name'],
            'level' => ['required', 'string', 'max:15'],
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
 * - id (mediumIncrements, auto)
 */