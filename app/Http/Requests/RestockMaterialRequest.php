<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestockMaterialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'qty'  => 'required|numeric|min:0.5',
            'note' => 'nullable|string|max:255',
        ];
    }
}