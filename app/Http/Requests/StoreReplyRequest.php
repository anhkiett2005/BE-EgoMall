<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreReplyRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        // Đảm bảo user đã đăng nhập và có quyền là nhân viên hoặc admin
        // return auth('api')->check();
        return true;
    }

    public function rules(): array
    {
        return [
            'review_id' => 'required|exists:reviews,id|unique:review_replies,review_id',
            'reply'     => 'required|string|min:5|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'review_id.required' => 'Thiếu mã đánh giá!',
            'review_id.exists'   => 'Đánh giá không tồn tại!',
            'review_id.unique'   => 'Đánh giá này đã được phản hồi!',
            'reply.required'     => 'Nội dung phản hồi không được để trống!',
            'reply.min'          => 'Nội dung phản hồi phải có ít nhất :min ký tự!',
            'reply.max'          => 'Nội dung phản hồi không được vượt quá :max ký tự!',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
