<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{

    use FormRequestResponseTrait;

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
            // 'slug' => ['required','string', Rule::unique('products', 'slug')->ignore($this->product)],
            'slug' => 'required|string',
            'is_active' => 'nullable|boolean|in:0,1',
            'brand_id' => 'required|exists:brands,id',
            'type_skin' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => ['required','url','regex:/\.(jpg|jpeg|png|gif|webp)$/i'],
            'category_id' => 'prohibited',
            'variants' => 'required|array|min:1',
        ];

        foreach ($this->input('variants', []) as $index => $variant) {
            $isNew = !isset($variant['id']); // nếu không có id là biến thể mới

            // Nếu biến thể cũ thì check id exists và sku unique bỏ qua id
            if (!$isNew) {
                $rules["variants.$index.id"] = 'exists:product_variants,id';
                $rules["variants.$index.sku"] = [
                    'required',
                    'numeric',
                    // Rule::unique('product_variants', 'sku')->ignore($variant['id'])
                ];
            } else {
                // biến thể mới thì bắt buộc option_id, value
                $rules["variants.$index.sku"] = ['required','numeric','unique:product_variants,sku'];

                $rules["variants.$index.options"] = 'required|array|min:1';
            }

            $rules["variants.$index.price"] = 'required|numeric|gt:0';
            $rules["variants.$index.sale_price"] = 'nullable|numeric|gt:0|lte:'.($variant['price'] ?? 'variants.'.$index.'.price');
            $rules["variants.$index.quantity"] = 'required|numeric|gt:0';
            $rules["variants.$index.is_active"] = 'nullable|boolean|in:0,1';
            $rules["variants.$index.images"] = 'required|array|min:1';

            foreach ($variant['images'] ?? [] as $imgIndex => $img) {
                // Nếu ảnh có id thì check exists, nếu không có thì bỏ qua
                if (isset($img['id'])) {
                    $rules["variants.$index.images.$imgIndex.id"] = 'exists:product_images,id';
                }
                $rules["variants.$index.images.$imgIndex.url"] = [
                    'required', 'url', 'regex:/\.(jpg|jpeg|png|gif|webp)$/i'
                ];
            }
        }

        return $rules;
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $validOptionIds = DB::table('variant_options')->pluck('id')->toArray(); // kiểm tra variant_option_id, đúng tên bảng

            foreach ($this->input('variants', []) as $index => $variant) {
                if (!isset($variant['options']) || !is_array($variant['options'])) {
                    continue;
                }

                foreach ($variant['options'] as $optionId => $optionValue) {
                    if (!in_array((int)$optionId, $validOptionIds)) {
                        $validator->errors()->add("variants.$index.options.$optionId", "Option ID $optionId không hợp lệ.");
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

    public function messages()
    {
        return [
            'name.required' => 'Tên sản phẩm là trường bắt buộc',
            'name.string' => 'Tên sản phẩm phải là chuỗi.',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',

            'slug.required' => 'Slug sản phẩm là trường bắt buộc',
            'slug.string' => 'Slug sản phẩm phải là chuỗi.',
            // 'slug.unique' => 'Slug sản phẩm đã tồn tại.',

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

            'variants.*.images.required' => 'Danh sách hình ảnh biến thể là bắt buộc.',
            'variants.*.images.array' => 'Hình ảnh biến thể phải là mảng.',
            'variants.*.images.min' => 'Hình ảnh biến thể phải có ít nhất một hình ảnh.',
            'variants.*.images.*.id.required' => 'Mã hình ảnh của sản phẩm biến thể không hợp lệ',
            'variants.*.images.*.id.exists' => 'Mã hình ảnh của sản phẩm biến thể không tốn tại',
            'variants.*.images.*.url.required' => 'Hình ảnh của biến thể là bắt buộc.',
            'variants.*.images.*.url.url' => 'Hình ảnh biến thể phải là URL.',
            'variants.*.images.*.url.regex' => 'Hình ảnh biến thể phải có định dạng jpg, jpeg, png, gif, webp.',

            'variants.*.options.required' => 'Giá trị của option là bắt buộc.',
            'variants.*.options.array'    => 'Giá trị của option phải là mảng.',
            'variants.*.options.min'      => 'Phải có ít nhất một option',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
