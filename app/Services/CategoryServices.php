<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Models\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;


class CategoryServices
{

    /**
     * Lấy toàn bộ danh sách categories
     */
    public function modifyIndex(Request $request)
    {
        try {
            $q = Category::with([
                'categoryOptions.variantOption',
                'children.categoryOptions.variantOption',
                'children.children.categoryOptions.variantOption',
                'children.children.children.categoryOptions.variantOption',
            ])->root();

            // lọc type
            if ($request->filled('type')) {
                $type = $request->input('type');
                if (!in_array($type, ['product', 'blog'], true)) {
                    throw new ApiException('Tham số type không hợp lệ!', Response::HTTP_BAD_REQUEST);
                }
                $q->where('type', $type);
            }

            // lọc is_active
            if ($request->has('is_active')) {
                $q->where('is_active', (int) $request->boolean('is_active'));
            }

            // lọc is_featured
            if ($request->has('is_featured')) {
                $q->where('is_featured', (int) $request->boolean('is_featured'));
            }

            $categories = $q->orderBy('name')->get();

            $list = $categories->map(function ($category) {
                return \App\Classes\Common::formatCategoryWithChildren($category);
            })->values();

            return $list;
        } catch (\Exception $e) {
            logger('Log bug modify category', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString(),
            ]);
            throw new ApiException('Không thể lấy danh mục!', Response::HTTP_INTERNAL_SERVER_ERROR);
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

    public function store($request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $type = $data['type'] ?? 'product';

            // Blog không được gửi options
            if ($type !== 'product' && !empty($data['options'])) {
                throw new ApiException('Loại "blog" không được chọn options!', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Slug an toàn (ưu tiên slug gửi lên, fallback theo name)
            $slug = Str::slug($data['slug'] ?? $data['name'] ?? '');

            $category = Category::create([
                'name'        => $data['name'],
                'slug'        => $slug,
                'parent_id'   => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'thumbnail'   => $data['thumbnail'] ?? null,
                'is_featured' => (int)($data['is_featured'] ?? 0),
                'type'        => $type,
            ]);

            // Chỉ tạo options khi là product
            if ($type === 'product' && !empty($data['options']) && is_array($data['options'])) {
                // loại trùng, lọc rỗng
                $optionIds = collect($data['options'])->filter()->unique()->values();

                foreach ($optionIds as $optionId) {
                    $category->categoryOptions()->create([
                        'variant_option_id' => (int)$optionId,
                    ]);
                }
            }

            DB::commit();
            return $category;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug store category', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString(),
            ]);
            throw new ApiException('Có lỗi xảy ra!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cập nhật một category
     */

    public function update($request, string $id)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();

            /** @var \App\Models\Category|null $category */
            $category = Category::with([
                'categoryOptions',
                'products' => fn($q) => $q->where('is_active', '!=', 0),
            ])->find($id);

            if (!$category) {
                throw new ApiException('Không tìm thấy danh mục!!', Response::HTTP_NOT_FOUND);
            }

            // Chặn gán parent là chính nó
            if (!empty($data['parent_id']) && (int)$data['parent_id'] === (int)$category->id) {
                throw new ApiException('parent_id không hợp lệ!', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $hasProducts = $category->products->count() > 0;
            $newType     = $data['type'] ?? $category->type;

            // type != product thì cấm gửi options
            $sentAnyOptions =
                array_key_exists('option_ids', $data) ||
                array_key_exists('options', $data) ||
                array_key_exists('variant_options', $data);

            if ($newType !== 'product' && $sentAnyOptions) {
                throw new ApiException('Loại "blog" không được gửi options!', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Cập nhật thông tin cơ bản
            $category->update([
                'name'        => $data['name'],
                'slug'        => Str::slug($data['slug'] ?? $data['name'] ?? $category->slug),
                'parent_id'   => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'thumbnail'   => $data['thumbnail'] ?? null,
                'is_active'   => $data['is_active'] ?? $category->is_active,
                'is_featured' => $data['is_featured'] ?? 0,
                'type'        => $newType,
            ]);

            // ======= Đồng bộ OPTIONS =======
            if ($newType === 'product') {
                // Nếu đã có sản phẩm => không cho thay đổi options
                if ($hasProducts && $sentAnyOptions) {
                    throw new ApiException('Danh mục đã có sản phẩm nên không thể cập nhật thuộc tính!', Response::HTTP_CONFLICT);
                }

                // Chỉ sync khi KHÔNG có product và có gửi options
                if (!$hasProducts && $sentAnyOptions) {
                    // Hỗ trợ 3 kiểu input
                    if (isset($data['option_ids']) && is_array($data['option_ids'])) {
                        $incoming = collect($data['option_ids']);
                    } elseif (isset($data['options']) && is_array($data['options'])) {
                        $incoming = collect($data['options']); // [1,2,3]
                    } elseif (isset($data['variant_options']) && is_array($data['variant_options'])) {
                        $incoming = collect($data['variant_options'])->pluck('id'); // [{id:1},...]
                    } else {
                        $incoming = collect();
                    }

                    $incoming = $incoming->filter()->unique()->values()->map(fn($v) => (int)$v);

                    $current  = $category->categoryOptions->pluck('variant_option_id');

                    $toInsert = $incoming->diff($current);
                    $toDelete = $current->diff($incoming);

                    if ($toDelete->isNotEmpty()) {
                        $category->categoryOptions()
                            ->whereIn('variant_option_id', $toDelete->all())
                            ->delete();
                    }
                    foreach ($toInsert as $optionId) {
                        $category->categoryOptions()->create([
                            'variant_option_id' => $optionId,
                        ]);
                    }
                }
            } else { // blog
                // Không có product thì xóa sạch options cũ (nếu còn)
                if (!$hasProducts && $category->categoryOptions()->exists()) {
                    $category->categoryOptions()->delete();
                }
                // Nếu có product thì KHÔNG đụng vào options (đảm bảo quy tắc không cập nhật options khi đã có product)
            }

            DB::commit();

            // Trả về bản ghi đã load lại quan hệ để FE dùng luôn
            return $category->load([
                'categoryOptions.variantOption',
                'children',
            ]);
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug update category', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!', Response::HTTP_INTERNAL_SERVER_ERROR);
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