<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class OrderRequest extends FormRequest
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
            // 'total_price' => 'required|numeric',
            // 'total_discount' => 'nullable|numeric',
            'note' => 'nullable|string',
            'shipping_name' => 'required|string',
            'shipping_phone' => 'required|numeric|regex:/^0[0-9]{9}$/',
            'shipping_email' => 'required|email',
            'shipping_address' => 'required|string',
            'payment_method' => 'required|in:COD,VNPAY,MOMO,ZALOPAY,SEPAY,PAYOS',
            'voucher_id' => 'nullable|exists:coupons,id',
            'shipping_method_id' => 'required|integer|exists:shipping_methods,id',
            'province_code' => 'required|integer|exists:provinces,code',

            // List danh sách order
            'orders' => 'required|array|min:1',
            'orders.*.products' => 'required|array|min:1',
            'orders.*.products.*.id' => 'required|exists:product_variants,id',
            'orders.*.products.*.quantity' => 'required|integer|min:1',
            // 'orders.*.products.*.price' => 'required|integer|gt:0',
            // 'orders.*.products.*.sale_price' => 'required|integer|gt:0|lte:orders.*.products.*.price',

            // List danh sách gift nếu sản phẩm có nằm trong chương trình promotion
            'orders.*.gifts' => 'nullable|array|min:1',
            'orders.*.gifts.*.id' => 'required|exists:product_variants,id',
            'orders.*.gifts.*.quantity' => 'required|integer|gt:0'
        ];
    }

    public function messages()
    {
        return [
            // 'total_price.required' => 'Vui lòng gửi số tiền thanh toán.',
            // 'total_price.numeric' => 'Số tiền thanh toán phải là số.',

            // 'total_discount.numberic' => 'Tổng tiền giảm giá phải là số.',

            'note.string' => 'Ghi chú phải là chuỗi.',

            'shipping_name.required' => 'Tên khách hàng là bắt buộc.',
            'shipping_name.string' => 'Tên khách hàng phải là chuỗi.',
            'shipping_phone.required' => 'Số điện thoại khách hàng là bắt buộc.',
            'shipping_phone.numeric' => 'Số điện thoại khách hàng phải là số.',
            'shipping_phone.regex' => 'Số điện thoại khách hàng không hợp lệ.',
            'shipping_email.required' => 'Email khách hàng là bắt buộc.',
            'shipping_email.email' => 'Email khách hàng không hợp lệ.',
            'shipping_address.required' => 'Địa chỉ khách hàng là bắt buộc.',
            'shipping_address.string' => 'Địa chỉ khách hàng phải là chuỗi.',

            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ. Chỉ hỗ trợ: COD, VNPAY, MOMO, ZaloPay.',
            'voucher_id.exists' => 'Voucher không hợp lệ.',

            'shipping_method_id.required' => 'Phương thức giao hàng là bắt buộc.',
            'shipping_method_id.integer' => 'Phương thức giao hàng phải là số.',
            'shipping_method_id.exists' => 'Phương thức giao hàng không hợp lệ.',

            'province_code.required' => 'Mã Tỉnh/Thành phố là bắt buộc.',
            'province_code.integer' => 'Mã Tỉnh/Thành phố phải là số.',
            'province_code.exists' => 'Mã Tỉnh/Thành phố không hợp lệ.',

            'orders.required' => 'Danh sách đơn hàng là bắt buộc.',
            'orders.array' => 'Danh sách đơn hàng phải là một mảng.',
            'orders.min' => 'Phải có ít nhất một đơn hàng.',

            'orders.*.products.required' => 'Danh sách sản phẩm của đơn hàng là bắt buộc.',
            'orders.*.products.array' => 'Danh sách sản phẩm của đơn hàng phải là một mảng.',
            'orders.*.products.min' => 'Phải có ít nhất một sản phẩm trong đơn hàng.',

            'orders.*.products.*.id.required' => 'Mã sản phẩm là bắt buộc.',
            'orders.*.products.*.id.exists' => 'Mã sản phẩm không hợp lệ.',
            'orders.*.products.*.quantity.required' => 'Số lượng sản phẩm là bắt buộc.',
            'orders.*.products.*.quantity.integer' => 'Số lượng sản phẩm phải là số nguyên.',
            'orders.*.products.*.quantity.min' => 'Số lượng sản phẩm phải ít nhất là 1.',
            // 'orders.*.products.*.price.required' => 'Giá sản phẩm là bắt buộc.',
            // 'orders.*.products.*.price.integer' => 'Giá sản phẩm phải là số nguyên.',
            // 'orders.*.products.*.price.gt' => 'Giá sản phẩm phải lớn hơn 0.',
            // 'orders.*.products.*.sale_price.required' => 'Giá giảm của sản phẩm là bắt buộc.',
            // 'orders.*.products.*.sale_price.integer' => 'Giá giảm của sản phẩm phải là số nguyên.',
            // 'orders.*.products.*.sale_price.gt' => 'Giá giảm của sản phẩm phải lớn hơn 0.',
            // 'orders.*.products.*.sale_price.lte' => 'Giá giảm của sản phẩm phải nhỏ hơn giá bán.',

            'orders.*.gifts.array' => 'Danh sách quà tặng của đơn hàng phải là một mảng.',
            'orders.*.gifts.min' => 'Phải có ít nhất một quà tặng trong đơn hàng.',

            'orders.*.gifts.*.id.required' => 'Mã quà tặng là bắt buộc.',
            'orders.*.gifts.*.id.exists' => 'Mã quà tặng không hợp lệ.',
            'orders.*.gifts.*.quantity.required' => 'Số lượng quà tặng là bắt buộc.',
            'orders.*.gifts.*.quantity.integer' => 'Số lượng quà tặng phải là số nguyên.',
            'orders.*.gifts.*.quantity.gt' => 'Số lượng quà tặng phải lớn hơn 0.',
        ];
    }

    // public function withValidator($validator) {
    //     $validator->after(function ($validator) {
    //         $orders = $this->input('orders', []);

    //         // Loop quà từng item trong list order để check giá có tồn tại trong hệ thống

    //         foreach($orders as $index => $orderItem) {
    //             $productIds = array_column($orderItem['products'], 'id');

    //             // check

    //             if(isset($productIds) && !empty($productIds)) {
    //                 $product = Product::with('variants')
    //                                   ->whereIn('id', $productIds)
    //                                   ->first();

    //                 if($product) {
    //                     foreach($product->variants as $variant) {
    //                         $price = $variant->sale_price ?? $variant->price;
    //                         // $salePrice = $variant->sale_price;

    //                         if(isset($orderItem['products'][$index]['price']) && $orderItem['products'][$index]['price'] != $price) {
    //                             $validator->errors()->add("orders.$index.products.$index.price", "Giá sản phẩm phải là không hợp lệ cho thanh toán này!!");
    //                         }

    //                         // if(isset($orderItem['products'][$index]['sale_price']) && $orderItem['products'][$index]['sale_price'] != $salePrice) {
    //                         //     $validator->errors()->add("orders.$index.products.$index.sale_price", "Giá giảm của sản phẩm phải là {$salePrice}đ.");
    //                         // }
    //                     }

    //                 }
    //             }
    //         }
    //     });
    // }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation errors',
            'code' => 422,
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
