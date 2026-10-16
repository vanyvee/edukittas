<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class ClassUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\MyClassUser::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id'    => ['required', 'integer', 'exists:users,id'],
            'class_id'   => ['required', 'integer', 'exists:my_classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'school_id'  => ['sometimes', 'integer', 'exists:my_schools,id'], // usually injected from auth context
            'cls_fee'    => ['nullable', 'integer', 'min:0', 'max:8388607'],
            'sub_type'   => ['required', 'in:s,a,b'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Composite primary key is (subject_id, class_id, user_id, school_id) —
            // catch a duplicate assignment cleanly instead of a raw DB integrity error.
            if ($this->filled(['user_id', 'class_id', 'subject_id', 'school_id'])) {
                $exists = DB::table('my_class_users')
                    ->where('user_id', $this->user_id)
                    ->where('class_id', $this->class_id)
                    ->where('subject_id', $this->subject_id)
                    ->where('school_id', $this->school_id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('subject_id', 'This teacher is already assigned to this subject and class.');
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
 * - uuid (generated server-side)
 * - timestamps
 */