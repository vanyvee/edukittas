<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_type'        => 'required|in:walkin,online',
            'payment_method'       => 'required|in:cash,transfer',
            'discount'             => 'sometimes|numeric|min:0',
            'customer_name'        => 'nullable|string|max:120',
            'customer_phone'       => 'nullable|string|max:20',
            'note'                 => 'nullable|string',
            'guest_token'          => 'nullable|string|max:100',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer|exists:products,id',
            'items.*.qty'          => 'required|integer|min:1',
            'items.*.price'        => 'nullable|numeric|min:0',
        ];
    }
}