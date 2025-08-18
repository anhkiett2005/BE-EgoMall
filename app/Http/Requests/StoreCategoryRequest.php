<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class StoreCategoryRequest extends FormRequest
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
            'slug' => 'required|string|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'thumbnail' => ['nullable', 'url', 'regex:/\.(jpg|jpeg|png|gif|webp)$/i'],
            'is_featured' => 'nullable|boolean|in:0,1',
            'type' => 'nullable|string|in:product,blog',

            // CHỈ cho phép khi type=product; blog thì cấm field này xuất hiện
            'options'     => 'prohibited_unless:type,product|nullable|array',
            'options.*'   => 'integer|exists:variant_options,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên danh mục là trường bắt buộc',
            'name.string' => 'Tên danh mục phải là chuỗi',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự.',

            'slug.required' => 'Slug danh mục là trường bắt buộc',
            'slug.string' => 'Slug danh mục phải là chuỗi',
            'slug.unique' => 'Slug danh mục tồn tại',

            'parent_id.exists' => 'Danh mục cha không tồn tại',

            'description.string' => 'Mô tả danh mục phải là chuỗi',

            'thumbnail.url' => 'Hình của danh mục phải là url hợp lệ',
            'thumbnail.regex' => 'Hình danh mục phải có dạng jpeg, png, jpg, gif hoặc webp.',

            'is_featured.boolean' => 'Trường nổi bật phải là boolean',
            'is_featured.in' => 'Trường nổi bật không hợp lệ',

            'type.string' => 'Loại danh mục phải là chuỗi',
            'type.in' => 'Loại danh mục không hợp lệ',

            'options.array' => 'Danh sách options phải là mảng',
            'options.min' => 'Danh sách options phải có ít nhất 1 options',
            'options.*.integer' => 'Các options phải là số',
            'options.*.exists' => 'Các options không tồn tại',
            'options.prohibited_unless' => 'Trường options chỉ được sử dụng khi loại danh mục là sản phẩm',
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
