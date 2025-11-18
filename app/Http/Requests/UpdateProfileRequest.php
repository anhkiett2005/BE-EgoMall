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
            'phone'   => 'nullable|string|regex:/^(0[3|5|7|8|9])+([0-9]{8})$\b/',
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
            'phone.regex' => 'Số điện thoại phải là số điện thoại Việt Nam hợp lệ (10 số, bắt đầu bằng 0).',

            'address.string' => 'Địa chỉ phải là chuỗi.',
            'address.max' => 'Địa chỉ tối đa 500 ký tự.',

            'imafe.image' => 'File phải là hình ảnh.',
            'image.max' => 'Kích thước hình ảnh tối đa 2MB.',
        ];
    }
}
