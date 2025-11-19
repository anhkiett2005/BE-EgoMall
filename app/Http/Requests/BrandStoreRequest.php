<?php

namespace App\Http\Requests;

use App\Traits\FormRequestResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BrandStoreRequest extends FormRequest
{

    use FormRequestResponseTrait;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:brands,name',
            'slug' => 'nullable|string|unique:brands,slug',
            'logo' => 'required|image|mimes:jpg,jpeg,png,svg,webp|max:10240',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tên thương hiệu là bắt buộc.',
            'name.string' => 'Tên thương hiệu phải là chuỗi.',
            'name.max' => 'Tên thương hiệu không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên thương hiệu đã tồn tại.',

            'slug.string' => 'Slug phải là chuỗi.',
            'slug.unique' => 'Slug đã tồn tại.',

            'logo.required' => 'Logo là bắt buộc.',
            'logo.image' => 'Logo phải là hình ảnh.',
            'logo.mimes' => 'Logo phải có định dạng jpg, jpeg, png, svg hoặc webp.',
            'logo.max' => 'Logo không được vượt quá 10MB.',

            'description.string' => 'Mô tả phải là chuỗi.',

            'is_active.boolean' => 'Trạng thái là bắt buộc.',
            'is_featured.boolean' => 'Nổi bật là bắt buộc.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $this->validationErrorResponse($validator->errors()->toArray());
    }
}
