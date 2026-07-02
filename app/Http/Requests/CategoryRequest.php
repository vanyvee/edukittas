<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:80|unique:categories,name,' . $this->route('id'),
            'sort_order' => 'sometimes|integer',
            'is_active'  => 'sometimes|boolean',
        ];
    }
}