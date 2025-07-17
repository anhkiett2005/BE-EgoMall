<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check(); // Có thể thêm RoleMiddleware nếu cần
    }

    public function rules(): array
    {
        return [
            'is_active' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required' => 'Trường trạng thái không được bỏ trống.',
            'is_active.boolean' => 'Trường trạng thái phải là true hoặc false.',
        ];
    }
}
