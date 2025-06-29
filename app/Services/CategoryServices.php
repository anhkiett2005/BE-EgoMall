<?php
namespace App\Services;

use App\Classes\Common;
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
            $categories = Category::with(['children.categoryOptions.variantOption'])
                                  ->root()
                                  ->where('is_active', '!=', 0)
                                  ->get();

            $listCategories = collect();

            $categories->each(function ($category) use ($listCategories) {
                // Đệ quy lấy all cây danh mục
                $listCategories->push(Common::formatCategoryWithChildren($category));
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

    /**
     * Cập nhật một category
     */

    public function update($request, string $slug)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
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
