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

            if ($isUpdated) {
                return ApiResponse::success('Cập nhật trạng thái đơn hàng thành công!!');
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    public function approveReturn(string $uniqueId)
    {
        try {
            $order = $this->orderService->approveReturn($uniqueId);

            return ApiResponse::success('Duyệt yêu cầu hoàn trả thành công!!', 200, data: [
                'unique_id'     => $order->unique_id,
                'status'        => $order->status,
                'return_status' => $order->return_status,
            ]);
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (\Throwable $e) {
            logger('Admin approve return error', ['e' => $e]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }

    public function rejectReturn(string $uniqueId)
    {
        try {
            $order = $this->orderService->rejectReturn($uniqueId);

            return ApiResponse::success('Từ chối yêu cầu hoàn trả thành công!!', 200, data: [
                'unique_id'     => $order->unique_id,
                'status'        => $order->status,
                'return_status' => $order->return_status,
            ]);
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (\Throwable $e) {
            logger('Admin reject return error', ['e' => $e]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }

    public function completeReturn(string $uniqueId)
    {
        try {
            $order = $this->orderService->completeReturn($uniqueId);

            return ApiResponse::success('Hoàn tất hoàn trả thành công!!', 200, data: [
                'unique_id'     => $order->unique_id,
                'status'        => $order->status,         // expect: return_sales
                'return_status' => $order->return_status,  // expect: completed
                'payment_status' => $order->payment_status, // expect: refunded (nếu paid)
                'payment_date'  => optional($order->payment_date)->toDateTimeString(),
                'transaction_id' => $order->transaction_id,
            ]);
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (\Throwable $e) {
            logger('Admin complete return error', ['e' => $e]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
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