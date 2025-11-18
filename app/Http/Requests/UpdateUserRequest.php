<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'phone'      => 'nullable|regex:/^0\d{9}$/',
            'image'      => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:10240',
            'is_active'  => 'required|boolean',
            'role_name'  => 'required|in:admin,staff',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Tên người dùng không được để trống!',
            'name.string'      => 'Tên người dùng phải là chuỗi ký tự!',
            'name.max'         => 'Tên người dùng không được vượt quá 255 ký tự!',

            'phone.regex'      => 'Số điện thoại không hợp lệ! Vui lòng nhập đúng định dạng: 10 chữ số bắt đầu bằng 0.',

            'image.image'      => 'Ảnh đại diện phải là một tệp hình ảnh!',
            'image.mimes'      => 'Ảnh đại diện phải có định dạng jpg, jpeg, png, svg hoặc webp!',
            'image.max'        => 'Ảnh đại diện không được vượt quá 10MB!',

            'is_active.required' => 'Trạng thái hoạt động là bắt buộc!',
            'is_active.boolean'  => 'Trạng thái hoạt động không hợp lệ!',

            'role_name.required' => 'Vai trò là bắt buộc!',
            'role_name.in'       => 'Vai trò không hợp lệ! (Chỉ cho phép admin, staff)',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
