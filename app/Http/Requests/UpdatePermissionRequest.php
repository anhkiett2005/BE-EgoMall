<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => [
                'required','string','regex:/^[a-z0-9._-]+$/','lowercase','max:100',
                Rule::unique('permissions','name')->ignore($id)->whereNull('deleted_at')
            ],
            'display_name' => ['required','string','max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập name.',
            'name.string' => 'Name phải là chuỗi.',
            'name.regex' => 'Name chỉ gồm chữ thường, số, ., _, -',
            'name.lowercase' => 'Name phải ở dạng chữ thường.',
            'name.max' => 'Name tối đa 100 ký tự.',
            'name.unique' => 'Name đã tồn tại.',
            'display_name.required' => 'Vui lòng nhập display_name.',
            'display_name.string' => 'Display name phải là chuỗi.',
            'display_name.max' => 'Display name tối đa 150 ký tự.',
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