<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|regex:/^0\d{9}$/',
            'password' => 'nullable|string|min:6|confirmed',
            'role_name' => 'required|in:admin,staff',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên người dùng không được để trống!',
            'name.string' => 'Tên người dùng phải là chuỗi ký tự!',
            'name.max' => 'Tên người dùng không được vượt quá 255 ký tự!',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự!',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự!',
            'image.image' => 'Ảnh đại diện phải là một tệp hình ảnh!',
            'image.mimes' => 'Ảnh đại diện phải có định dạng jpg, jpeg, png, svg hoặc webp!',
            'image.max' => 'Ảnh đại diện không được vượt quá 10MB!',
            'email.required' => 'Email không được để trống!',
            'email.email' => 'Email không hợp lệ!',
            'email.unique' => 'Email đã tồn tại!',
            'password.string' => 'Mật khẩu phải là chuỗi ký tự!',
            'password.min' => 'Mật khẩu phải từ 6 ký tự!',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp!',
            'role_name.required' => 'Vai trò là bắt buộc!',
            'role_name.in' => 'Vai trò không hợp lệ!',
        ];
    }
}