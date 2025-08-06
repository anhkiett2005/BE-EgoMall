<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
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
}