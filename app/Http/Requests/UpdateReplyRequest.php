<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReplyRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        // return auth('api')->check();
        return true;
    }

    public function rules(): array
    {
        return [
            'reply' => 'required|string|min:5|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reply.required' => 'Nội dung phản hồi không được để trống!',
            'reply.min'      => 'Nội dung phản hồi phải có ít nhất :min ký tự!',
            'reply.max'      => 'Nội dung phản hồi không được vượt quá :max ký tự!',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
