<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UpdateRoleAndPermissionRequest extends FormRequest
{

    use FormRequestResponseTrait;

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

        $roleId = $this->route('roleId');


        return [
            'role' => 'required|array',
            'role.name' => ['required','string', Rule::unique('roles', 'name')->ignore($roleId)],
            'role.display_name' => ['required','string', Rule::unique('roles', 'display_name')->ignore($roleId)],
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }

    public function messages()
    {
        return [
            'role.required' => 'Vui lòng gửi lên vai trò cần cập nhật.',
            'role.array' => 'Vai trò cần cập nhật phải là một object.',
            'role.name.required' => 'Vui lòng gửi lên tên vai trò cần cập nhật.',
            'role.name.string' => 'Tên vai trò cần cập nhật phải là chuỗi.',
            'role.name.unique' => 'Tên vai trò cần cập nhật đã tồn tại.',
            'role.display_name.required' => 'Vui lòng gửi lên tên hiển thị cho vai trò cần cập nhật.',
            'role.display_name.string' => 'Tên hiển thị cho vai trò cần cập nhật phải là chuỗi.',
            'role.display_name.unique' => 'Tên hiển thị cho vai trò cần cập nhật đã tồn tại.',

            'permissions.required' => 'Vui lòng chọn ít nhất một quyền.',
            'permissions.*.integer' => 'Quyền phải là số.',
            'permissions.*.exists' => 'Quyền không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
