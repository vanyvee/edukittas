<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            
            'first_name' => 'required|string|max:120',
            'surname'    => 'required|string|max:120',
            'username'   => 'nullable|string|max:120',
            'sex'   => 'nullable|string|max:12',
            'phone'      => 'required|string|max:20',
              'role'       => 'sometimes|in:ceo,supervisor,cook,cashier,waiter',
            'address'    => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
            'day'    => 'sometimes|integer|min:1|max:31',
            'month'  => 'sometimes|integer|min:1|max:12',
            'year'   => 'sometimes|integer|min:1970',
        ];
    }
}