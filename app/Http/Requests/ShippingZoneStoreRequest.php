<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ShippingZoneStoreRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        // return auth('api')->check();
        return true;
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

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
