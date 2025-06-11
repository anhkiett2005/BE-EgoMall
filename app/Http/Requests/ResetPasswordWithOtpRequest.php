<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordWithOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'                     => 'required|email|exists:users,email',
            'otp'                       => 'required|string|size:6',
            'new_password'              => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'                     => 'Bạn phải nhập email.',
            'email.email'                        => 'Email không đúng định dạng.',
            'email.exists'                       => 'Email này chưa đăng ký trong hệ thống.',
            'otp.required'                       => 'Bạn phải nhập mã OTP.',
            'otp.size'                           => 'Mã OTP gồm 6 chữ số.',
            'new_password.required'              => 'Bạn phải nhập mật khẩu mới.',
            'new_password.min'                   => 'Mật khẩu mới tối thiểu 8 ký tự.',
            'new_password.confirmed'             => 'Xác nhận mật khẩu không khớp.',
            'new_password_confirmation.required' => 'Bạn phải nhập xác nhận mật khẩu mới.',
        ];
    }
}
