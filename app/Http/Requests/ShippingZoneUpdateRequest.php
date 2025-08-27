<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingZoneUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'fee'          => 'required|numeric|min:0',
            'is_available' => 'nullable|boolean',
        ];
    }
}