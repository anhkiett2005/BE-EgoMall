<?php
namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Category;
use Exception;
use Illuminate\Support\Facades\DB;

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

     /**
     * Tạo mới một category
     */

    public function store($request)
    {
        $data = $request->all();
        DB::beginTransaction();
        try {
            // tạo danh mục
            $category = Category::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'parent_id' => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'is_featured' => $data['is_featured'] ?? 0,
                'type' => $data['type'] ?? 'product',
            ]);

            // Nếu có gán các options cho variants thì thêm vào category_options
            if(!empty($data['options']) && is_array($data['options'])) {
                foreach($data['options'] as $optionId) {
                    $category->categoryOptions()->create([
                        'variant_option_id' => $optionId,
                    ]);
                }
            }

            DB::commit();
            return $category;

        } catch(Exception $e) {
            DB::rollBack();
            logger('Log bug',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }
}
