<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brandId = $this->route('id'); // Lấy id từ route

        return [
            'name' => 'required|string|max:255|unique:brands,name,' . $brandId,
            'slug' => 'nullable|string|unique:brands,slug,' . $brandId,
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }
}
