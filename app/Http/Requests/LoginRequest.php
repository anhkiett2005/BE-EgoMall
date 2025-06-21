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

    protected function prepareForValidation()
    {
        $this->merge([
            'account' => preg_replace('/\s+/', '', $this->account),
        ]);
    }

    public function rules(): array
    {
        return [
            'account' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (preg_match('/^\d+$/', $value)) {
                        // Là số thì check phải la so dien thoai
                        if (!preg_match('/^0[0-9]{9,10}$/', $value)) {
                            $fail('Số điện thoại không hợp lệ.');
                        }
                    } else {
                        // Là chuỗi thì check phải là email k
                        if (!Str::contains($value, '@') && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $fail('Email không hợp lệ.');
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
