<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category_id' => 'required|integer|exists:categories,id',
            'name'        => 'required|string|max:80',
            'sort_order'  => 'sometimes|integer',
            'is_active'   => 'sometimes|boolean',
        ];
    }
}