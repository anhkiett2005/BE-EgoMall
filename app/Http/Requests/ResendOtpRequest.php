<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Bạn phải nhập email.',
            'email.email'    => 'Email không đúng định dạng.',
            'email.exists'   => 'Email này chưa đăng ký.',
        ];
    }
}
