<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ShippingZoneRequest extends FormRequest
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

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
