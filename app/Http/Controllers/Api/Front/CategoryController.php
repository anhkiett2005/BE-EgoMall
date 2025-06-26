<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Resources\Front\FrontCategoryResource;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Response\ApiResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Lấy tất cả danh mục gốc kèm danh mục con và brand
        $categories = Category::with('children')
                                ->root()
                                ->featured()
                                ->select('id','name','slug','thumbnail','is_active','is_featured')
                                ->get()
                                ->map(function ($category) {
                                    $category->children->each(function ($child) {
                                        $child->makeHidden(['created_at', 'updated_at','parent_id']);
                                    });
                                    return $category;
                                });

        return ApiResponse::success('Data fetched successfully',data: $categories);
    }
}
