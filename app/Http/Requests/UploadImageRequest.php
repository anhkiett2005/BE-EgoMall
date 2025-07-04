<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Classes\Common;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class UploadImageRequest extends FormRequest
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
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'folder' => 'required|string',
            'upload_token' => ['required', 'string', function ($attribute, $value, $fail) {
                if(!Common::isValidUploadToken($value)) {
                    $fail('Token không hợp lệ, vui lòng thử lại sau!!');
                }
            }]
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'Vui lòng chọn file.',
            'file.image' => 'File phải là ảnh.',
            'file.mimes' => 'File phải là jpeg, png, jpg, gif hoặc webp.',
            'file.max' => 'Kích thước file khoâng vụt quá 10MB.',

            'folder.required' => 'Vui lòng chỉ định folder để upload.',
            'folder.string' => 'Folder không hợp lệ.',
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
