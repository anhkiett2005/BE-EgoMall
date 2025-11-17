<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ShippingZoneUpdateRequest extends FormRequest
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
            'fee'          => 'required|numeric|min:0',
            'is_available' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'fee.required' => 'Phí vận chuyển không được để trống.',
            'fee.numeric' => 'Phí vận chuyển phải là số.',

            'is_available.boolean' => 'Trạng thái hoạt động phải la true hoặc false.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
