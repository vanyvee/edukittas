<?php

namespace App\Http\Requests;

use App\Models\Cbt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the CBT's own settings (title, type, timing). The questions are
 * validated separately by App\Services\Cbt\QuestionValidator.
 */
class CbtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership is checked in the controller
    }

    public function rules(): array
    {
        // On update every field is optional, so a rename does not need the whole payload.
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'title' => [$required, 'string', 'max:255'],
            'type' => [$required, Rule::in(Cbt::TYPES)],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /** Only the CBT's own columns; `blocks` is handled by QuestionValidator. */
    public function meta(): array
    {
        return $this->safe()->only([
            'title', 'type', 'duration_minutes', 'opens_at', 'closes_at', 'expires_at',
        ]);
    }
}
