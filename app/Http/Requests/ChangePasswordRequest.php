<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        // Chỉ user đã auth mới được đổi mật khẩu
        // return auth('api')->check();
        return true;
    }

    public function rules(): array
    {
        return [
            'old_password'              => 'required|string',
            'new_password'              => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.required'              => 'Bạn phải nhập mật khẩu cũ.',
            'new_password.required'              => 'Bạn phải nhập mật khẩu mới.',
            'new_password.min'                   => 'Mật khẩu mới tối thiểu 8 ký tự.',
            'new_password.confirmed'             => 'Xác nhận mật khẩu không khớp.',
            'new_password_confirmation.required' => 'Bạn phải nhập xác nhận mật khẩu mới.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
