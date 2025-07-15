<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Models\Category;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CategoryServices
{

    /**
     * Lấy toàn bộ danh sách categories
     */

    public function modifyIndex()
    {
        try {
            $categories = Category::with(['children.categoryOptions.variantOption'])
                ->root()
                // ->where('is_active', '!=', 0)
                ->get();

            $listCategories = collect();

            $categories->each(function ($category) use ($listCategories) {
                // Đệ quy lấy all cây danh mục
                $listCategories->push(Common::formatCategoryWithChildren($category));
            });

            return $listCategories;
        } catch (Exception $e) {
            logger('Log bug modify product', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }

    /**
     * Lấy chi tiết một category
     */
    public function show(string $slug)
    {
        try {
            // Tìm category
            $category = Category::with(['children.categoryOptions.variantOption'])
                                ->where('slug', '=', $slug)
                                ->first();

            // check nếu k có danh mục thriw exception
            if (!$category) {
                throw new ApiException('Không tìm thấy danh mục!!', Response::HTTP_NOT_FOUND);
            }

            $categoryDetail = collect();

            $categoryDetail->push(Common::formatCategoryWithChildren($category));

            return $categoryDetail;

        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            logger('Log bug show category', [
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
            if (!empty($data['options']) && is_array($data['options'])) {
                foreach ($data['options'] as $optionId) {
                    $category->categoryOptions()->create([
                        'variant_option_id' => $optionId,
                    ]);
                }
            }

            DB::commit();
            return $category;
        } catch (Exception $e) {
            DB::rollBack();
            logger('Log bug', [
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

            // find danh mục để update
            $category = Category::with(
                [
                    'categoryOptions',
                    'products' => function ($query) {
                        $query->where('is_active', '!=', 0);
                    }
                ]
            )
                ->where('slug', '=', $slug)
                ->first();


            // check nếu có product thì k cho cập nhật options
            $hasProducts = $category->products->count() > 0 ? true : false;

            if ($hasProducts && isset($data['variant_options'])) {
                throw new ApiException('Danh mục đã có sản phẩm nên không thể cập nhật thuộc tính!', Response::HTTP_CONFLICT);
            }

            // Nếu k có products vẫn cập nhật danh mục và các variant_options
            $category->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'parent_id' => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'is_active' => $data['is_active'],
                'is_featured' => $data['is_featured'] ?? 0,
                'type' => $data['type'] ?? 'product',
            ]);

            // update các options hoặc create nếu ch có kèm với product
            if (!$hasProducts && !empty($data['variant_options']) && is_array($data['variant_options'])) {
                $existingOptions = $category->categoryOptions->keyBy('variant_option_id');

                foreach ($data['variant_options'] as $option) {
                    $optionId = $option['id'];

                    // check if exits options
                    if ($existingOptions->has($optionId)) {
                        $existingOptions[$optionId]->update([
                            'variant_option_id' => $optionId,
                        ]);
                    } else {
                        // tạo mới nếu ch có
                        $category->categoryOptions()->create([
                            'variant_option_id' => $optionId
                        ]);
                    }
                }
            }

            DB::commit();
            return $category;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }

    /**
     * Xóa một category
     */
    public function destroy(string $slug)
    {
        DB::beginTransaction();

        try {
            // Tìm danh mục muốn xóa
            $category = Category::where('slug', '=', $slug)
                ->first();

            // Nếu không tìm thấy trả về lỗi
            if (!$category) {
                throw new ApiException('Không tìm thấy danh mục!!', 404);
            }

            // Xóa product
            $category->delete();

            DB::commit();

            return $category;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            logger('Log bug delete category', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }
}
