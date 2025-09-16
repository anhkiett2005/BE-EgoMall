<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class SystemSettingStoreRequest extends FormRequest
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
            '*.setting_key'   => 'required',
            '*.setting_value' => 'required',
            '*.setting_type'  => 'required',
            '*.setting_group' => 'required',
            '*.setting_label' => 'required',
            '*.description'   => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            '*.setting_key.required'   => 'Khóa cấu hình là bắt buộc.',
            '*.setting_value.required' => 'Giá trị cấu hình là bắt buộc.',
            '*.setting_type.required'  => 'Loại cấu hình là bắt buộc.',
            '*.setting_group.required' => 'Nhóm cấu hình là bắt buộc.',
            '*.setting_label.required' => 'Tiêu đề cấu hình là bắt buộc.',
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
