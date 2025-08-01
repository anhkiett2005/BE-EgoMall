<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class StoreProductRequest extends FormRequest
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
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug',
            // 'images' => 'required|array|min:1',
            // 'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'required|boolean|in:0,1',
            'type_skin' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => ['required','url','regex:/\.(jpg|jpeg|png|gif|webp)$/i'],

            'variants' => 'required|array',
            'variants.*.sku' => 'required|numeric|unique:product_variants,sku',
            'variants.*.price' => 'required|numeric',
            'variants.*.sale_price' => 'nullable|numeric',
            'variants.*.quantity' => 'required|numeric',
            'variants.*.is_active' => 'required|boolean|in:0,1',
            'variants.*.options' => 'required|array|min:1',
            'variants.*.images' => 'required|array|min:1',
            'variants.*.images.*.url' => ['required','url','regex:/\.(jpg|jpeg|png|gif|webp)$/i'],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            // Trường chung
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'name.string' => 'Tên sản phẩm phải là chuỗi.',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',

            // 'images.required' => 'Danh sách hình ảnh là bắt buộc.',
            // 'images.array' => 'Danh sách hình ảnh phải là mảng.',
            // 'images.min' => 'Phải có ít nhất một hình ảnh.',
            // 'images.*.required' => 'Mỗi hình ảnh là bắt buộc.',
            // 'images.*.image' => 'Mỗi file phải là hình ảnh.',
            // 'images.*.mimes' => 'Hình ảnh phải có định dạng jpeg, png, jpg, gif, hoặc svg.',
            // 'images.*.max' => 'Kích thước mỗi hình ảnh không vượt quá 2MB.',

            'category_id.required' => 'Danh mục là bắt buộc.',
            'category_id.exists' => 'Danh mục không tồn tại.',

            'brand_id.exists' => 'Thương hiệu không tồn tại.',

            // 'is_variable.required' => 'Trường phân loại sản phẩm là bắt buộc.',
            // 'is_variable.boolean' => 'Trường phân loại sản phẩm phải là true hoặc false.',
            // 'is_variable.in' => 'Trường phân loại sản phẩm không hợp lệ.',

            'is_active.required' => 'Trạng thái hoạt động là bắt buộc.',
            'is_active.boolean' => 'Trạng thái hoạt động phải là true hoặc false.',
            'is_active.in' => 'Trạng thái hoạt động không hợp lệ.',

            'type_skin.string' => 'Loại da phải là chuỗi.',
            'description.string' => 'Mô tả phải là chuỗi.',

            'image.required' => 'Hình ảnh là bắt buộc.',
            'image.url' => 'Hình ảnh phải là một đường dẫn hợp lệ.',
            'image.regex' => 'Hình ảnh phải có định dạng jpeg, jpg, png, gif, hoặc webp.',

            // Trường khi không phải sản phẩm biến thể
            'slug.required' => 'Slug là bắt buộc.',
            'slug.string' => 'Slug phải là chuỗi.',
            'slug.unique' => 'Slug của sản phẩm đã tồn tại.',

            // Trường khi là sản phẩm biến thể
            'variants.required' => 'Danh sách biến thể là bắt buộc.',
            'variants.array' => 'Biến thể phải là một mảng.',

            'variants.*.sku.required' => 'Mã sản phẩm của biến thể là bắt buộc.',
            'variants.*.sku.numeric' => 'Mã sản phẩm của biến thể phải là số.',
            'variants.*.sku.unique' => 'Mã sản phẩm của biến thể đã tồn tại.',

            'variants.*.price.required' => 'Giá của biến thể là bắt buộc.',
            'variants.*.price.numeric' => 'Giá của biến thể phải là số.',

            'variants.*.sale_price.numeric' => 'Giá khuyến mãi của biến thể phải là số.',

            'variants.*.quantity.required' => 'Số lượng của biến thể là bắt buộc.',
            'variants.*.quantity.numeric' => 'Số lượng của biến thể phải là số.',

            'variants.*.is_active.required' => 'Trạng thái hoạt động của biến thể là bắt buộc.',
            'variants.*.is_active.boolean' => 'Trạng thái hoạt động của biến thể phải là true hoặc false.',

            'variants.*.options.required' => 'Biến thể phải có ít nhất một tùy chọn.',
            'variants.*.options.array' => 'Tùy chọn của biến thể phải là mảng.',
            'variants.*.options.min' => 'Mỗi biến thể phải có ít nhất một tùy chọn.',

            'variants.*.images.required' => 'Danh sách hình ảnh của biến thể là bắt buộc.',
            'variants.*.images.array' => 'Danh sách hình ảnh của biến thể phải là mảng.',
            'variants.*.images.min' => 'Mỗi biến thể phải có ít nhất một hình ảnh.',

            'variants.*.images.*.url.required' => 'Hình ảnh của biến thể là bắt buộc.',
            'variants.*.images.*.url.url' => 'Hình ảnh của biến thể phải là một đường dẫn hợp lệ.',
            'variants.*.images.*.url.regex' => 'Hình ảnh của biến thể phải có định dạng jpeg, jpg, png, gif, hoặc webp.',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $validOptionIds = DB::table('variant_options')->pluck('id')->toArray();

            foreach($this->variants as $index => $variant) {
                if(!isset($variant['options']) || !is_array($variant['options'])) {
                    continue;
                }

                foreach ($variant['options'] as $optionId => $optionValue) {
                    if (!in_array((int)$optionId, $validOptionIds)) {
                        $validator->errors()->add("variants.$index.options.$optionId", "Option Id $optionId không hợp lệ.");
                    }

                    // Validate thêm option value (required, string, max:255)

                    // Kiểm tra required
                    if ($optionValue === null || $optionValue === '' || (is_array($optionValue) && empty($optionValue))) {
                        $validator->errors()->add("variants.$index.options.$optionId", "Giá trị của Option là bắt buộc.");
                        continue;
                    }

                    // Kiểm tra kiểu chuỗi
                    if (!is_string($optionValue)) {
                        $validator->errors()->add("variants.$index.options.$optionId", "Giá trị của Option phải là chuỗi.");
                        continue;
                    }

                    // Kiểm tra độ dài tối đa
                    if (mb_strlen($optionValue) > 255) {
                        $validator->errors()->add("variants.$index.options.$optionId", "Giá trị của Option không được vượt quá 255 ký tự.");
                    }
                }
            }
        });
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
