<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StoreRoleAndPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Bắt buộc mảng role, có đủ 2 key
            'role' => ['required', 'array', 'required_array_keys:name,display_name'],

            // Tên role: chữ thường + số + . _ -, max 50, không trùng role active, không đè tên role hệ thống
            'role.name' => [
                'required', 'string', 'alpha_dash:ascii', 'lowercase', 'max:50',
                Rule::unique('roles', 'name')->whereNull('deleted_at'),
                'not_in:super-admin,admin,staff,customer',
            ],

            // Display name: max 100, unique trên role active
            'role.display_name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'display_name')->whereNull('deleted_at'),
            ],

            // Permissions: tối thiểu 1, không trùng, tồn tại
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Vui lòng gửi lên thông tin vai trò.',
            'role.array' => 'Vai trò phải là một mảng.',
            'role.required_array_keys' => 'Vai trò phải có đủ các trường: name, display_name.',

            'role.name.required' => 'Vui lòng nhập tên vai trò.',
            'role.name.string' => 'Tên vai trò phải là chuỗi.',
            'role.name.alpha_dash' => 'Tên vai trò chỉ gồm chữ, số, dấu gạch ngang hoặc gạch dưới (ASCII).',
            'role.name.lowercase' => 'Tên vai trò phải ở dạng chữ thường.',
            'role.name.max' => 'Tên vai trò tối đa 50 ký tự.',
            'role.name.unique' => 'Tên vai trò đã tồn tại.',
            'role.name.not_in' => 'Tên vai trò trùng với vai trò hệ thống, không được phép tạo.',

            'role.display_name.required' => 'Vui lòng nhập tên hiển thị.',
            'role.display_name.string' => 'Tên hiển thị phải là chuỗi.',
            'role.display_name.max' => 'Tên hiển thị tối đa 100 ký tự.',
            'role.display_name.unique' => 'Tên hiển thị đã tồn tại.',

            'permissions.required' => 'Vui lòng chọn ít nhất một quyền.',
            'permissions.array' => 'Danh sách quyền phải là mảng.',
            'permissions.min' => 'Phải chọn ít nhất một quyền.',
            'permissions.*.integer' => 'ID quyền phải là số.',
            'permissions.*.distinct' => 'Danh sách quyền không được trùng.',
            'permissions.*.exists' => 'Quyền không hợp lệ.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Tự hạ chữ thường cho role.name và ép kiểu permissions -> int
        $payload = $this->all();

        if (isset($payload['role']['name']) && is_string($payload['role']['name'])) {
            $payload['role']['name'] = strtolower($payload['role']['name']);
        }

        if (isset($payload['permissions']) && is_array($payload['permissions'])) {
            $payload['permissions'] = array_map('intval', $payload['permissions']);
        }

        $this->replace($payload);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation errors',
            'code' => 422,
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}