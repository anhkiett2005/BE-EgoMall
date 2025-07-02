<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StorePromotionRequest extends FormRequest
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
            'name' => 'required|string',
            'description' => 'nullable|string',
            'promotion_type' => ['required', Rule::in(['percentage', 'buy_get'])],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',

            // Validate khi promotion_type là percentage
            'discount_type' => ['required_if:promotion_type,percentage', 'nullable', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required_if:promotion_type,percentage', 'nullable', 'numeric', 'gt:0'],

            // Fields dùng khi là buy_get
            'buy_quantity' => ['required_if:promotion_type,buy_get', 'nullable', 'integer', 'min:1'],
            'get_quantity' => ['required_if:promotion_type,buy_get', 'nullable', 'integer', 'min:1'],
            'gift_product_id' => [
                'nullable',
                'required_if:promotion_type,buy_get',
                'required_without:gift_product_variant_id',
                'exists:products,id',
            ],

            'gift_product_variant_id' => [
                'nullable',
                'required_if:promotion_type,buy_get',
                'required_without:gift_product_id',
                'exists:product_variants,id',
            ],


            // Gắn sản phẩm khuyến mãi
            'applicable_products' => 'required|array|min:1',
            'applicable_products.*' => [
                'required',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];

                    $productId = request("applicable_products.$index.product_id");
                    $variantId = request("applicable_products.$index.variant_id");

                    if (!$productId && !$variantId) {
                        return $fail("Phải chọn ít nhất sản phẩm hoặc biến thể cho mục #".($index+1));
                    }

                    if ($productId && !Product::where('id', $productId)->exists()) {
                        return $fail("Sản phẩm được chọn trong mục #".($index+1)." không tồn tại.");
                    }

                    if ($variantId && !ProductVariant::where('id', $variantId)->exists()) {
                        return $fail("Biến thể được chọn trong mục #".($index+1)." không tồn tại.");
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên chương trình khuyến mãi là bắt buộc.',
            'name.string' => 'Tên chương trình khuyến mãi phải là chuỗi ký tự.',

            'description.string' => 'Mô tả phải là một chuỗi ký tự.',

            'promotion_type.required' => 'Loại khuyến mãi là bắt buộc.',
            'promotion_type.in' => 'Loại khuyến mãi không hợp lệ. Chỉ chấp nhận "percentage" hoặc "buy_get" hoặc "fixed_amount".',

            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.date' => 'Ngày bắt đầu không đúng định dạng.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.date' => 'Ngày kết thúc không đúng định dạng.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',

            // Percentage
            'discount_type.required_if' => 'Loại giảm giá là bắt buộc khi chọn kiểu khuyến mãi phần trăm.',
            'discount_type.in' => 'Loại giảm giá không hợp lệ. Chỉ chấp nhận "percentage" hoặc "fixed_amount".',
            'discount_value.required_if' => 'Giá trị giảm giá là trường bắt buộc.',
            'discount_value.numeric' => 'Giá trị giảm giá phải là số.',
            'discount_value.gt' => 'Giá trị giảm giá phải lớn hơn hoặc bằng 0.',

            // Buy_get
            'buy_quantity.required_if' => 'Số lượng mua là bắt buộc khi chọn khuyến mãi mua tặng.',
            'buy_quantity.integer' => 'Số lượng mua phải là số nguyên.',
            'buy_quantity.min' => 'Số lượng mua phải ít nhất là 1.',
            'get_quantity.required_if' => 'Số lượng tặng là bắt buộc khi chọn khuyến mãi mua tặng.',
            'get_quantity.integer' => 'Số lượng tặng phải là số nguyên.',
            'get_quantity.min' => 'Số lượng tặng phải ít nhất là 1.',

            'gift_product_id.required_if' => 'Vui lòng chọn sản phẩm hoặc biến thể làm quà tặng khi áp dụng khuyến mãi mua tặng.',
            'gift_product_id.required_without' => 'Vui lòng chọn sản phẩm làm quà tặng hoặc chọn một biến thể cụ thể.',
            'gift_product_id.exists' => 'Sản phẩm được chọn để làm quà tặng không tồn tại.',

            // Gift Product Variant ID
            'gift_product_variant_id.required_if' => 'Vui lòng chọn sản phẩm hoặc biến thể làm quà tặng khi áp dụng khuyến mãi mua tặng.',
            'gift_product_variant_id.required_without' => 'Vui lòng chọn một biến thể làm quà tặng hoặc chọn sản phẩm chung.',
            'gift_product_variant_id.exists' => 'Biến thể được chọn để làm quà tặng không tồn tại.',

            // Sản phẩm áp dụng
            'applicable_products.required' => 'Vui lòng chọn ít nhất một sản phẩm áp dụng khuyến mãi.',
            'applicable_products.array' => 'Danh sách sản phẩm áp dụng phải là mảng.',
            'applicable_products.min' => 'Vui lòng chọn ít nhất một sản phẩm áp dụng khuyến mãi.',
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
