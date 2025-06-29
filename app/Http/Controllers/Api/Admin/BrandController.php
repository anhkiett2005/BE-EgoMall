<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Response\ApiResponse;
use App\Services\BrandServices;
use Illuminate\Http\Request;
use App\Http\Requests\BrandStoreRequest;
use App\Http\Requests\BrandUpdateRequest;
use App\Http\Resources\Admin\BrandResource;

class BrandController extends Controller
{

    protected $brandService;

    public function __construct(BrandServices $brandService)
    {
        $this->brandService = $brandService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = $this->brandService->modifyIndex();

        return ApiResponse::success('Lấy danh sách thương hiệu thành công!!', data: $brands);
    }

    public function show(string $id)
    {
        $brand = $this->brandService->modifyShow($id);
        return ApiResponse::success('Lấy thương hiệu thành công!', data: (new BrandResource($brand))->resolve());
    }

    public function store(BrandStoreRequest $request)
    {
        $brand = $this->brandService->modifyStore($request->validated());
        return ApiResponse::success('Tạo thương hiệu thành công!');
    }

    public function update(BrandUpdateRequest $request, string $id)
    {
        $brand = $this->brandService->modifyUpdate($request->validated(), $id);
        return ApiResponse::success('Cập nhật thương hiệu thành công!');
    }

    public function destroy(string $id)
    {
        $this->brandService->modifyDestroy($id);
        return ApiResponse::success('Xóa thương hiệu thành công!');
    }

    public function trashed()
    {
        $brands = $this->brandService->modifyTrashed();
        return ApiResponse::success('Lấy danh sách thương hiệu đã xoá!', data: $brands->resolve());
    }

    public function restore(string $id)
    {
        $brand = $this->brandService->modifyRestore($id);
        return ApiResponse::success('Khôi phục thương hiệu thành công!', data: $brand->resolve());
    }
}