<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmDetailsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'uuid'       => 'nullable|string|uuid',
            'confirmed'  => 'required|boolean',
            'first_name' => 'required_if:confirmed,false|string|max:120',
            'surname'    => 'required_if:confirmed,false|string|max:120',
            'sex'        => 'required_if:confirmed,false|string|max:12',
            'phone'      => 'required_if:confirmed,false|string|max:20',
            'address'    => 'required_if:confirmed,false|string|max:255',
        ];
    }
}