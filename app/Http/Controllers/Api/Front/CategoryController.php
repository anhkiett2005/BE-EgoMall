<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Resources\Front\FrontCategoryResource;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Response\ApiResponse;
use App\Services\CategoryServices;
use Illuminate\Http\Request;

class CategoryController extends Controller
{


    protected $categoryService;

    public function __construct(CategoryServices $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
{
    try {
        $type = $request->get('type');

        $query = Category::with('children')
            ->root()
            ->where('is_active', true)
            ->select('id', 'name', 'slug', 'thumbnail', 'is_active', 'is_featured', 'type');

        if (!empty($type)) {
            $query->where('type', $type);
        }

        $categories = $query->get()
            ->map(function ($category) {
                $category->children->each(function ($child) {
                    Common::formatCategoryWithChildren($child->makeHidden([
                        'created_at', 'updated_at', 'parent_id'
                    ]));
                });
                return $category;
            });

        return ApiResponse::success('Lấy danh sách danh mục thành công!', data: $categories);
    } catch (\Exception $e) {
        logger('Log bug danh mục', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        throw new ApiException('Có lỗi xảy ra!!', 500);
    }
}
}
