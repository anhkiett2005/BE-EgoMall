<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug',
            'is_active' => 'nullable|boolean|in:0,1',
            'brand_id' => 'required|exists:brands,id',
            'type_skin' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => ['required','url','regex:/\.(jpg|jpeg|png|gif|webp)$/i'],

            // Không cho update danh mục
            'category_id' => 'prohibited',

            'variants' => 'required|array|min:1',
            'variants.*.id'         => 'exists:product_variants,id',
            'variants.*.sku'        => 'required|numeric|unique:product_variants,sku',
            'variants.*.price'      => 'required|numeric|gt:0',
            'variants.*.sale_price' => 'nullable|numeric|gt:0|lte:variants.*.price',
            'variants.*.quantity'   => 'required|numeric|gt:0',
            'variants.*.is_active'  => 'nullable|boolean|in:0,1',
            'variants.*.image' => 'required|array|min:1',
            'variants.*.image.*.id' => 'required|exists:product_images,id',
            'variants.*.image.*.url' => ['required','url','regex:/\.(jpg|jpeg|png|gif|webp)$/i'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên sản phẩm là trường bắt buộc',
            'name.string' => 'Tên sản phẩm phải là chuỗi.',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',

            'slug.required' => 'Slug sản phẩm là trường bắt buộc',
            'slug.string' => 'Slug sản phẩm phải là chuỗi.',
            'slug.unique' => 'Slug sản phẩm đã tồn tại.',

            'is_active.boolean' => 'Trạng thái hoạt động phải là true hoặc false.',
            'is_active.in' => 'Trạng thái hoạt động không hợp lệ.',

            'brand_id.required' => 'Thương hiệu là trường bắt buộc.',
            'brand_id.exists' => 'Thương hiệu không tồn tại.',

            'type_skin.string' => 'Loại da phải là chuỗi.',

            'description.string' => 'Mô tả sản phẩm phải là chuỗi.',

            'image.required' => 'Hình ảnh sản phẩm là trường bắt buộc.',
            'image.url' => 'Hình ảnh sản phẩm phải là URL.',
            'image.regex' => 'Hình ảnh sản phẩm phải có định dạng jpeg, png, jpg, gif hoặc webp.',

            'category_id.prohibited' => 'Không được phép thay đổi danh mục sản phẩm.',

            'variants.required' => 'Danh sách biến thể sản phẩm là trường bắt buộc.',
            'variants.array' => 'Danh sách biến thể sản phẩm phải là mảng.',
            'variants.min' => 'Phải có ít nhất 1 biến thể',

            'variants.*.id.exists' => 'Mã biến thể không tồn tại',

            'variants.*.sku.required' => 'SKU biến thể là trường bắt buộc',
            'variants.*.sku.numeric' => 'SKU biến thể phải là số.',
            'variants.*.sku.unique' => 'SKU biến thể đã tồn tại.',

            'variants.*.price.required' => 'Giá biến thể là trường bắt buộc',
            'variants.*.price.numeric' => 'Giá biến thể phải là số.',
            'variants.*.price.gt' => 'Giá biến thể phải lớn hơn 0.',

            'variants.*.sale_price.numeric' => 'Giá giảm biến thể phải là số.',
            'variants.*.sale_price.gt' => 'Giá giảm biến thể phải lớn hơn 0.',
            'variants.*.sale_price.lte' => 'Giá khuyến mãi không được lớn hơn giá gốc.',

            'variants.*.quantity.required' => 'Vui lòng gửi lên số lượng biến thể.',
            'variants.*.quantity.numeric' => 'Số lượng biến thể phải là số.',
            'variants.*.quantity.gt' => 'Số lượng biến thể phải lớn hơn 0.',

            'variants.*.is_active.boolean' => 'Trạng thái hoạt động của biến thể phải là true hoặc false.',
            'variants.*.is_active.in' => 'Trạng thái hoạt động của biến thể không hợp lệ.',

            'variants.*.image.required' => 'Danh sách hình ảnh biến thể là bắt buộc.',
            'variants.*.image.array' => 'Hình ảnh biến thể phải là mảng.',
            'variants.*.image.min' => 'Hình ảnh biến thể phải có ít nhất một hình ảnh.',
            'variants.*.image.*.id.required' => 'Mã hình ảnh của sản phẩm biến thể không hợp lệ',
            'variants.*.image.*.id.exists' => 'Mã hình ảnh của sản phẩm biến thể không tốn tại',
            'variants.*.image.*.url.required' => 'Hình ảnh của biến thể là bắt buộc.',
            'variants.*.image.*.url.url' => 'Hình ảnh biến thể phải là URL.',
            'variants.*.image.*.url.regex' => 'Hình ảnh biến thể phải có định dạng jpg, jpeg, png, gif, webp.',
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
