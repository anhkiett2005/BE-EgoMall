<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserAddressRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth('api')->id();
        $addressId = $this->route('id');

        $uniqueAddressRule = Rule::unique('user_addresses')
            ->where(function ($query) use ($userId) {
                return $query->where('user_id', $userId)
                    ->where('province_code', $this->province_code)
                    ->where('district_code', $this->district_code)
                    ->where('ward_code', $this->ward_code)
                    ->where('address_detail', $this->address_detail);
            });

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $uniqueAddressRule = $uniqueAddressRule->ignore($addressId);
        }

        return [
            'province_code' => ['required', 'exists:provinces,code'],
            'district_code' => ['required', 'exists:districts,code'],
            'ward_code'     => ['required', 'exists:wards,code'],
            'address_detail' => ['required', 'string', 'max:255'],
            'address_name'  => ['nullable', 'string', 'max:255'],
            'first_name'    => ['required', 'string', 'max:50'],
            'last_name'     => ['required', 'string', 'max:50'],
            'email'         => ['required', 'email'],
            'phone'         => ['required', 'regex:/^0\d{9}$/'],
            'note'          => ['nullable', 'string'],
            'is_default'    => ['nullable', 'boolean'],
            // Không được trùng địa chỉ
            'address_detail' => [$uniqueAddressRule],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Họ không được để trống!',
            'last_name.required'  => 'Tên không được để trống!',
            'province_code.exists' => 'Tỉnh/Thành không hợp lệ!',
            'district_code.exists' => 'Quận/Huyện không hợp lệ!',
            'ward_code.exists'     => 'Phường/Xã không hợp lệ!',
            'phone.regex'          => 'Số điện thoại không hợp lệ!',
            'email.email'          => 'Email không hợp lệ!',
            'address_detail.unique' => 'Địa chỉ này đã tồn tại trong hệ thống của bạn!',
            'address_detail.required' => 'Chi tiết địa chỉ không được để trống!',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
