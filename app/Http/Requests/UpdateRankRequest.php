<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class UpdateRankRequest extends FormRequest
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
            'rankDetails' => 'required|array|min:1',
            'rankDetails.*.name' => 'required|string|max:255|unique:ranks,name',
            'rankDetails.*.image' => ['nullable','url','regex:/\.(jpg|jpeg|png|gif|webp)$/i'],
            'rankDetails.*.amount_to_point' => 'required|numeric',
            'rankDetails.*.min_spent_amount' => 'nullable|numeric|required_without:rankDetails.*.minimum_point|prohibits:rankDetails.*.minimum_point',
            'rankDetails.*.converted_amount' => 'required|numeric',
            'rankDetails.*.discount' => 'nullable|numeric',
            'rankDetails.*.maximum_discount_order' => 'required_if:rankDetails.*.checked,==,true',
            'rankDetails.*.type_time_receive' => 'nullable|string|max:255',
            'rankDetails.*.time_receive_point' => 'nullable|string|max:255',
            'rankDetails.*.minimum_point' => 'nullable|numeric|required_without:rankDetails.*.min_spent_amount|prohibits:rankDetails.*.min_spent_amount',
            'rankDetails.*.maintenance_point' => 'nullable|numeric',
            'rankDetails.*.point_limit_transaction' => 'nullable|numeric',
            'rankDetails.*.status_payment_point' => 'nullable|boolean|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'rankDetails.required' => 'Vui lòng gửi lên danh sách rank.',
            'rankDetails.array' => 'Danh sách rank phải là mảng.',
            'rankDetails.min' => 'Danh sách rank phải có ít nhất 1 rank.',

            'rankDetails.*.name.required' => 'Tên rank là bắt buộc.',
            'rankDetails.*.name.string' => 'Tên rank phải là chuỗi.',
            'rankDetails.*.name.max' => 'Tên rank không quá 255 ký tự.',
            'rankDetails.*.name.unique' => 'Tên rank bị trùng.',

            'rankDetails.*.image.url' => 'Hình ảnh phải là url hợp lệ.',
            'rankDetails.*.image.regex' => 'Hình ảnh phải là jpeg, png, jpg, gif, hoặc webp.',

            'rankDetails.*.amount_to_point.required' => 'Vui lòng thiết lập số tiền đổi điểm.',
            'rankDetails.*.amount_to_point.numeric' => 'Số tiền đổi điểm phải là số.',

            'rankDetails.*.min_spent_amount.numeric' => 'Điều kiện tổng chi tiêu rank phải là số.',
            'rankDetails.*.min_spent_amount.required_without' => 'Vui lòng nhập tổng chi tiêu nếu không thiết lập điểm tích lũy.',
            'rankDetails.*.min_spent_amount.prohibits' => 'Không thể nhập tổng chi tiêu khi đã thiết lập điểm tích lũy.',


            'rankDetails.*.converted_amount.required' => 'Vui lòng thiết lập số điểm đổi tiền.',
            'rankDetails.*.converted_amount.numeric' => 'Số điểm đổi tiền phải là số.',

            'rankDetails.*.discount.numeric' => 'Giảm giá phải là số.',

            'rankDetails.*.maximum_discount_order.required_if' => 'Vui lòng thiết lập giảm giá lớn nhất trên đơn hàng.',

            'rankDetails.*.type_time_receive.string' => 'Chu kỳ nhận điểm phải la chuỗi.',
            'rankDetails.*.type_time_receive.max' => 'Chu kỳ nhận điểm không quá 255 ký tự.',

            'rankDetails.*.time_receive_point.string' => 'Giá trị chu kỳ nhận điểm phải la chuỗi.',
            'rankDetails.*.time_receive_point.max' => 'Giá trị chu kỳ nhận điểm không quá 255 ký tự.',

            'rankDetails.*.minium_point.numeric' => 'Điểm tích lũy phải là số.',
            'rankDetails.*.minimum_point.required_without' => 'Vui lòng nhập điểm tích lũy nếu không thiết lập tổng chi tiêu.',
            'rankDetails.*.minimum_point.prohibits' => 'Không thể nhập điểm tích lũy khi đã thiết lập tổng chi tiêu.',

            'rankDetails.*.maintenance_point.numeric' => 'Điểm tối thiểu duy trì rank phải là số.',

            'rankDetails.*.point_limit_transaction.numeric' => 'Số điểm giao dịch tối đa phải là số.',

            'rankDetails.*.status_payment_point.boolean' => 'Trạng thái thanh toán điểm phải là true hoặc false.',
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
