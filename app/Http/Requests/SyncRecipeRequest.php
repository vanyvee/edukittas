<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRecipeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'materials'               => 'required|array|min:1',
            'materials.*.material_id' => 'required|integer|exists:raw_materials,id',
            'materials.*.qty'         => 'required|numeric|min:0.001',
        ];
    }
}