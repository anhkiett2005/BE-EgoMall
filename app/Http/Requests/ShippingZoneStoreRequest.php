<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingZoneStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'province_codes'   => 'required|array|min:1',
            'province_codes.*' => 'string|max:20|exists:provinces,code',
            'fee'              => 'required|numeric|min:0',
            'is_available'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'province_codes.required' => 'Vui lòng chọn ít nhất 1 tỉnh/thành.',
            'province_codes.*.exists' => 'Mã tỉnh/thành không hợp lệ.',
            'fee.required'            => 'Phí vận chuyển không được để trống.',
            'fee.numeric'             => 'Phí vận chuyển phải là số.',
        ];
    }
}