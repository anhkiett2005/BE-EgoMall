<?php
namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Category;
use Exception;

class CategoryServices {

    /**
     * Lấy toàn bộ danh sách categories
     */

     public function modifyIndex()
     {
        try {
            $categories = Category::with('children')
                                  ->root()
                                  ->where('is_active', '!=', 0)
                                  ->get();

            $listCategories = collect();

            $categories->each(function ($category) use ($listCategories) {
                $listCategories->push([
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'thumbnail' => $category->thumbnail,
                    'is_active' => $category->is_active,
                    'is_featured' => $category->is_featured,
                    'type' => $category->type,
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                            'description' => $child->description,
                            'thumbnail' => $child->thumbnail,
                            'is_active' => $child->is_active,
                            'is_featured' => $child->is_featured,
                        ];
                    }),
                    'created_at' => $category->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $category->updated_at->format('Y-m-d H:i:s'),
                ]);
            });

            return $listCategories;
        } catch (Exception $e) {
            logger('Log bug modify product',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
     }
}
