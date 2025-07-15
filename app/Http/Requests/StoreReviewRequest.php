<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền kiểm tra ở middleware
    }

    public function rules(): array
    {
        return [
            'order_detail_id' => 'required|exists:order_details,id',
            'rating'          => 'required|integer|min:1|max:5',
            'comment'         => 'nullable|string|max:1000',
            'is_anonymous'    => 'sometimes|boolean',
            'images.*'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'order_detail_id.required' => 'Thiếu thông tin sản phẩm trong đơn hàng.',
            'order_detail_id.exists'   => 'Sản phẩm này không tồn tại trong hệ thống.',
            'rating.required'          => 'Vui lòng chọn số sao đánh giá.',
            'rating.integer'           => 'Số sao phải là số nguyên.',
            'rating.min'               => 'Số sao tối thiểu là 1.',
            'rating.max'               => 'Số sao tối đa là 5.',
            'images.*.image'           => 'Tệp tải lên phải là hình ảnh.',
            'images.*.mimes'           => 'Ảnh phải có định dạng jpeg, png, jpg, hoặc webp.',
            'images.*.max'             => 'Kích thước mỗi ảnh không được vượt quá 10MB.',
        ];
    }
}