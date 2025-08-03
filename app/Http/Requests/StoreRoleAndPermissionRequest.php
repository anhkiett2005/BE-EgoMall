<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class StoreRoleAndPermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => 'required|array',
            'role.name' => 'required|string|unique:roles,name',
            'role.display_name' => 'required|string',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }

    public function messages()
    {
        return [
            'role.required' => 'Vui lòng gửi lên vai trò cần tạo.',
            'role.array' => 'Vai trò cần tạo phải là một mảng.',
            'role.min' => 'Vai trò cần tạo phải có ít nhất 1 vai trò.',
            'role.name.required' => 'Vui lòng gửi lên tên vai trò cần tạo.',
            'role.name.string' => 'Tên vai trò cần tạo phải là chuỗi.',
            'role.name.unique' => 'Tên vai trò cần tạo đã tồn tại.',
            'role.display_name.required' => 'Vui lòng gửi lên tên hiển thị cho vai trò cần tạo.',
            'role.display_name.string' => 'Tên hiển thị cho vai trò cần tạo phải là chuỗi.',
            'permissions.required' => 'Vui lòng gửi lên quyền cần tạo.',
            'permissions.array' => 'Quyền cần tạo phải là một mảng.',
            'permissions.min' => 'Quyền cần tạo phải có ít nhất 1 quyền.',
            'permissions.*.integer' => 'Quyền cần tạo phải là số.',
            'permissions.*.exists' => 'Quyền cần tạo không tồn tại.',
        ];
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
