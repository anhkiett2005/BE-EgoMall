<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Response\ApiResponse;
use App\Services\ProductServices;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{

    protected $productService;

    public function __construct(ProductServices $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = $this->productService->modifyIndex();

        return ApiResponse::success('Lấy danh sách sản phẩm thành công!!',data: $products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->store($request);

            if($product) {
                return ApiResponse::success('Tạo sản phẩm thành công!!',Response::HTTP_CREATED);
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $product = $this->productService->showProduct($slug);

        if($product) {
            return ApiResponse::success('Lấy chi tiết sản phẩm thành công!!',data: $product);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $slug)
    {
        try {
            $isUpdated = $this->productService->update($request, $slug);

            if($isUpdated) {
                return ApiResponse::success('Cập nhật sản phẩm thành công');
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
            $isDeleted = $this->productService->destroy($slug);

            if($isDeleted) {
                return ApiResponse::success('Xóa sản phẩm thành công!!');
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }
}
