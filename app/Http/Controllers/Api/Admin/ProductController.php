<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
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

        return response()->json([
            'message' => 'Data Fetched Successfully',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->store($request);

            if($product) {
                return response()->json([
                    'success' => true,
                    'message' => 'Resource Created Successfully'
                ], Response::HTTP_CREATED);
            }
        } catch (ApiException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->getCode() == 422 ? $e->getErrors() : []
            ], $e->getStatus() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $product = $this->productService->showProduct($slug);

        if($product) {
            return response()->json([
                'message' => 'Data Fetched Successfully',
                'data' => $product
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
