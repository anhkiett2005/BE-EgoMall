<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UpdateCategoryRequest extends FormRequest
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
        $id = Category::where('slug', '=', $this->slug)->value('id');
        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                Rule::unique('categories', 'slug')->ignore($id)
            ],
            'parent_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'thumbnail' => ['nullable', 'url', 'regex:/\.(jpg|jpeg|png|gif|webp)$/i'],
            'is_active' => 'nullable|boolean|in:0,1',
            'is_featured' => 'nullable|boolean|in:0,1',
            'type' => 'required|string|in:product,blog',

            // Đổi về cùng 1 tên field để nhất quán
            'option_ids'   => 'prohibited_unless:type,product|nullable|array',
            'option_ids.*' => 'integer|exists:variant_options,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên danh mục là trường bắt buộc',
            'name.string' => 'Tên danh mục phải là chuỗi',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự',

            'slug.required' => 'Slug danh mục là trường bắt buộc',
            'slug.string' => 'Slug danh mục phải là chuỗi',
            'slug.unique' => 'Slug danh mục đã tồn tại',

            'parent_id.integer' => 'ID danh mục cha phải là số',
            'parent_id.exists' => 'ID danh mục cha không tồn tại',

            'description.string' => 'Mô tả danh mục phải là chuỗi',

            'thumbnail.url' => 'Hình ảnh danh mục phải là URL hợp lệ',
            'thumbnail.regex' => 'Hình ảnh danh mục phải có định dạng jpg, jpeg, png, gif hoặc webp',


            'is_active.boolean' => 'Trang thai danh mục phải là boolean',
            'is_active.in' => 'Trang thai danh mục phải là true hoặc false',

            'is_featured.boolean' => 'Trạng thái nổi bật danh mục phải là boolean',
            'is_featured.in' => 'Trạng thái nổi bật danh mục phải là true hoặc false',

            'type.required' => 'Loại danh mục là trường bắt buộc',
            'type.string' => 'Loại danh mục phải là chuỗi',
            'type.in' => 'Loại danh mục phải là product hoặc blog',

            'variant_options.required' => 'Danh sách options là trường bắt buộc',
            'variant_options.array' => 'Danh sách options phải là mảng',
            'variant_options.min' => 'Danh sách options phải có ít nhất 1 options',
            'variant_options.*.id.required' => 'ID option là trường bắt buộc',
            'variant_options.*.id.integer' => 'ID option phải là số',
            'variant_options.*.id.exists' => 'ID option không tồn tại',
            'option_ids.prohibited_unless' => 'Trường option_ids chỉ được sử dụng khi loại danh mục là sản phẩm',
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
