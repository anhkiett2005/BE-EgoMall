<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->isMethod('post') ? $this->createRules() : $this->updateRules();
    }

    protected function createRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:blogs,slug'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string'],
            'image_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:10240'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'archived'])],
            'category_id' => ['required', 'exists:categories,id'],
            'published_at' => ['nullable', 'date'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    protected function updateRules(): array
    {
        $blogId = $this->route('id');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', Rule::unique('blogs', 'slug')->ignore($blogId)],
            'content' => ['sometimes', 'string'],
            'excerpt' => ['nullable', 'string'],
            'image_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:10240'],
            'status' => ['sometimes', Rule::in(['draft', 'scheduled', 'published', 'archived'])],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'published_at' => ['nullable', 'date'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $categoryId = $this->input('category_id');

            if ($categoryId) {
                $exists = DB::table('categories')
                    ->where('id', $categoryId)
                    ->where('type', 'blog')
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add('category_id', 'Danh mục không hợp lệ hoặc không tồn tại trong danh mục blog.');
                }
            }
        });
    }
}
