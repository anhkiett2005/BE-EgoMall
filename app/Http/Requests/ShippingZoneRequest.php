<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'province_code' => 'required|string|max:20',
            'fee'           => 'required|numeric|min:0',
            'is_available'  => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'province_code.required' => 'Mã tỉnh/thành là bắt buộc.',
            'fee.required'           => 'Phí vận chuyển không được để trống.',
            'fee.numeric'            => 'Phí vận chuyển phải là số.',
        ];
    }
}
