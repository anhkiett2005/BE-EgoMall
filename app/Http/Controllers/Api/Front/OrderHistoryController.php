<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\Front\OrderDetailResource;
use App\Http\Resources\Front\OrderHistoryResource;
use App\Response\ApiResponse;
use App\Services\OrderHistoryService;
use Illuminate\Http\Request;

class OrderHistoryController extends Controller
{
    protected $orderHistoryService;

    public function __construct(OrderHistoryService $orderHistoryService)
    {
        $this->orderHistoryService = $orderHistoryService;
    }

    public function index(Request $request)
    {
        $status = $request->get('status');

        $orders = $this->orderHistoryService->listByUser($status);

        return ApiResponse::success(
            'Danh sách đơn hàng của bạn',
            200,
            OrderHistoryResource::collection($orders)->toArray($request)
        );
    }

    public function show(string $uniqueId)
    {
        try {
            $order = $this->orderHistoryService->getByUniqueId($uniqueId);

            return ApiResponse::success(
                'Chi tiết đơn hàng',
                200,
                (new OrderDetailResource($order))->toArray(request())
            );
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}