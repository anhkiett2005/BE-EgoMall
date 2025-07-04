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
            'image_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'category_id' => ['required', 'exists:categories,id'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
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
            'image_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
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