<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Nếu có dấu @, coi là email
                    if (Str::contains($value, '@')) {
                        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $fail('Email không hợp lệ.');
                        }
                    }
                    else {
                        // Ngược lại coi là số điện thoại
                        if (! preg_match('/^0[0-9]{9,10}$/', $value)) {
                            $fail('Số điện thoại không hợp lệ.');
                        }
                    }
                },
            ],
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'account.required'  => 'Vui lòng nhập email hoặc số điện thoại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min'      => 'Mật khẩu phải ít nhất 6 ký tự.',
        ];
    }
}
