<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cho phép mọi request (có thể check thêm nếu muốn)
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Bạn phải nhập email.',
            'email.email'    => 'Email không đúng định dạng.',
            'email.exists'   => 'Email này chưa đăng ký.',
            'otp.required'   => 'Bạn phải nhập mã OTP.',
            'otp.size'       => 'Mã OTP gồm 6 chữ số.',
        ];
    }
}