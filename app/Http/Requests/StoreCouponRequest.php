<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{

    use FormRequestResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons')->whereNull('deleted_at')
            ],
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percent,amount',
            'discount_value' => 'required|numeric|gte:0',
            'min_order_value' => 'nullable|numeric|gte:0',
            'max_discount' => 'nullable|numeric|gte:0',
            'usage_limit' => 'nullable|integer|gte:0',
            'discount_limit' => 'nullable|integer|gte:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'code.required' => 'Mã voucher là bắt buộc.',
            'code.string' => 'Mã voucher phải là chuỗi.',
            'code.max' => 'Mã voucher tối đa 50 ký tự.',
            'code.unique' => 'Mã voucher này đã tồn tại trong hệ thống.',

            'description.string' => 'Mô tả phải là chuỗi.',
            'description.max' => 'Mô tả tối đa 255 ký tự.',

            'discount_type.required' => 'Loại giảm giá là bắt buộc.',
            'discount_type.in' => 'Loại giảm giá khó hợp lệ. Chỉ chấp nhận "percent" hoặc "amount".',

            'discount_value.required' => 'Giá trị giảm giá là bắt buộc.',
            'discount_value.numeric' => 'Giá trị giảm giá phải là số.',
            'discount_value.gte' => 'Giá trị giảm giá phải lớn hơn 0.',

            'min_order_value.numeric' => 'Giá trị hóa đơn phải là số.',
            'min_order_value.gte' => 'Giá trị hóa đơn phải lớn hơn 0.',

            'max_discount.numeric' => 'Giá trị giảm giá tối đa phải là số.',
            'max_discount.gte' => 'Giá trị giảm giá tối đa phải lớn hơn 0.',

            'usage_limit.integer' => 'Giá trị sử dụng toàn phần phải là số nguyên.',
            'usage_limit.min' => 'Giá trị sử dụng toàn phần phải lớn hơn 0.',

            'discount_limit.integer' => 'Giá trị sử dụng tối đa phải là số nguyên.',
            'discount_limit.gte' => 'Giá trị giảm giá tối đa phải lớn hơn 0.',

            'start_date.date' => 'Ngày bắt đầu phải là ngày hợp lệ.',
            'end_date.date' => 'Ngày kết thúc phải là ngày hợp lệ.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn ngày bắt đầu.',

            'status.required' => 'Trạng thái là bắt buộc.',
            'status.boolean' => 'Trạng thái phải là true hoặc false.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
