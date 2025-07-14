<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Response\ApiResponse;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = $this->orderService->modifyIndex();

        return ApiResponse::success('Lấy danh sách đơn hàng thành công!!', data: $orders);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uniqueId)
    {
        try {
            $order = $this->orderService->show($uniqueId);

            return ApiResponse::success('Lấy chi tiết đơn hàng thành công!!', data: $order);
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, string $uniqueId)
    {
        try {
            $isUpdated = $this->orderService->updateStatus($request, $uniqueId);

            if($isUpdated) {
                return ApiResponse::success('Cập nhật trạng thái đơn hàng thành công!!');
            }
        }catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
