<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromotionRequest;
use App\Response\ApiResponse;
use App\Services\PromotionServices;
use Illuminate\Http\Request;

class PromotionController extends Controller
{

    protected $promotionServices;

    public function __construct(PromotionServices $promotionServices)
    {
        $this->promotionServices = $promotionServices;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $promotions = $this->promotionServices->modifyIndex();

            if($promotions) {
                return ApiResponse::success('Lấy danh sách khuyến mãi thành công!!',data: $promotions);
            }
        } catch(ApiException $e) {
            return ApiResponse::error($e->getMessage(),$e->getCode(),$e->getErrors());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromotionRequest $request)
    {
        try {
            $promotion = $this->promotionServices->store($request);

            if($promotion) {
                return ApiResponse::success('Tạo chương trình khuyến mãi thành công!!');
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(),$e->getCode(),$e->getErrors());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $promotion = $this->promotionServices->show($id);

            if($promotion) {
                return ApiResponse::success('Lấy thông tin chương trình thành công!!',data: $promotion);
            }
        } catch(ApiException $e) {
            return ApiResponse::error($e->getMessage(),$e->getCode(),$e->getErrors());
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
