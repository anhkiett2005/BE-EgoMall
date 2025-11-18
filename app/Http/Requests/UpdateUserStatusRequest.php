<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserStatusRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        return true;
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

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
