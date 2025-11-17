<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cho phép mọi user đã auth
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:10',
            'address' => 'nullable|string|max:500',
            'image'   => 'nullable|image|max:2048', // tối đa 2MB
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên là trường bắt buộc.',
            'name.string' => 'Tên phải là chuỗi.',
            'name.max' => '	Tên tối đa 255 ký tự.',

            'phone.string' => 'Số điện thoại phải là chuỗi.',
        ];
    }
}
