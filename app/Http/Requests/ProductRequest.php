<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'sub_category_id' => 'required|integer|exists:sub_categories,id',
            'name'            => 'required|string|max:160',
            'price'           => 'nullable|numeric|min:0',
            'prep_minutes'    => 'nullable|integer|min:1',
            'image_url'       => 'nullable|string|max:255',
            'is_price_open'   => 'sometimes|boolean',
            'available'       => 'sometimes|boolean',
            'sort_order'      => 'sometimes|integer',
        ];
    }
}