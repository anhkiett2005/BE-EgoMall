<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VariantOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'required|string|max:255|unique:variant_options,name' . ($id ? ",$id" : ''),
            'values' => 'nullable|array',
            'values.*' => 'string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên tùy chọn là bắt buộc!',
            'name.string'   => 'Tên tùy chọn phải là chuỗi!',
            'name.max'      => 'Tên tùy chọn tối đa 255 ký tự!',
            'name.unique'   => 'Tên tùy chọn đã tồn tại!',

            'values.array' => 'Giá trị phải là một mảng!',
            'values.*.string' => 'Mỗi giá trị phải là chuỗi!',
            'values.*.max' => 'Mỗi giá trị tối đa 255 ký tự!',
        ];
    }
}