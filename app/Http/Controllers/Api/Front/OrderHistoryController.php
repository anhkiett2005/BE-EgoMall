<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
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
            OrderHistoryResource::collection($orders)
        );
    }
}
