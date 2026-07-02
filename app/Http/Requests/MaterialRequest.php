<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:120',
            'unit'      => 'required|string|max:20',
            'stock'     => 'sometimes|numeric|min:0',
            'threshold' => 'sometimes|numeric|min:0',
        ];
    }
}