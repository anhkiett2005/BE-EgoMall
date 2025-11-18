<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        return true; // Đã kiểm tra user_id ở Service rồi
    }

    public function rules(): array
    {
        return [
            'rating'        => 'nullable|integer|min:1|max:5',
            'comment'       => 'nullable|string|max:1000',
            'is_anonymous'  => 'nullable|boolean',
            'images'        => 'nullable|array|max:5',
            'images.*'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.integer'     => 'Đánh giá phải là số nguyên!',
            'rating.min'         => 'Đánh giá thấp nhất là 1 sao!',
            'rating.max'         => 'Đánh giá cao nhất là 5 sao!',
            'comment.max'        => 'Bình luận không được vượt quá 1000 ký tự!',
            'images.max'        => 'Bạn chỉ được cập nhật tối đa 5 ảnh!',
            'images.*.image'     => 'Tệp phải là hình ảnh!',
            'images.*.mimes'     => 'Hình ảnh phải định dạng jpeg, png, jpg, hoặc webp!',
            'images.*.max'       => 'Mỗi ảnh không vượt quá 10MB!',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
