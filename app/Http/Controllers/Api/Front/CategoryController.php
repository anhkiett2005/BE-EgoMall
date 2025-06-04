<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Resources\Front\FrontCategoryResource;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Lấy tất cả danh mục gốc kèm danh mục con và brand
        $categories = Category::with(['children','brand'])
                                ->root()
                                ->get();

         // Lấy danh mục nổi bật
        $featuredCategories = Category::featured()
                                       ->select('thumbnail','slug')
                                       ->get();
        return response()->json([
            'message' => 'success',
            'data' => [
                'menu' => FrontCategoryResource::collection($categories),
                'featuredCategories' => $featuredCategories,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        //
    }
}
