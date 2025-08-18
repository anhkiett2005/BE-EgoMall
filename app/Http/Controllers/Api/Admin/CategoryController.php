<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Response\ApiResponse;
use App\Services\CategoryServices;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
    public function index()
    {
        $categories = $this->categoryService->modifyIndex();

        return ApiResponse::success('Lấy danh sách danh mục thành công!!', data: $categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $category = $this->categoryService->store($request);

            if ($category) {
                return ApiResponse::success('Thêm danh mục thành công!!', Response::HTTP_CREATED);
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        try {
            $category = $this->categoryService->show($slug);

            if ($category) {
                return ApiResponse::success('Lấy chi tiết danh mục thành công!!', data: $category);
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, string $id)
    {
        try {
            $isUpdated = $this->categoryService->update($request, $id);


            $data = is_array($isUpdated) ? $isUpdated : $isUpdated->toArray();

            if ($isUpdated) {
                return ApiResponse::success('Cập nhật danh mục thành công!!', data: $data);
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $slug)
    {
        try {
            $isDeleted = $this->categoryService->destroy($slug);

            if ($isDeleted) {
                return ApiResponse::success('Xóa danh mục thành công!!');
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }
}