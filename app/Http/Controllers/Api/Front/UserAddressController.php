<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserAddressRequest;
use App\Http\Resources\Front\UserAddressResource;
use App\Response\ApiResponse;
use App\Services\UserAddressService;

class UserAddressController extends Controller
{
    protected UserAddressService $service;

    public function __construct(UserAddressService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $userId = auth('api')->id();
        $addresses = $this->service->listByUser($userId);

        return ApiResponse::success(
            'Lấy danh sách địa chỉ thành công!',
            200,
            UserAddressResource::collection($addresses)->toArray(request())
        );
    }

    public function show(int $id)
    {
        $userId = auth('api')->id();
        $address = $this->service->getById($id, $userId);

        return ApiResponse::success(
            'Lấy chi tiết địa chỉ thành công!',
            200,
            (new UserAddressResource($address))->toArray(request())
        );
    }

    public function store(UserAddressRequest $request)
    {
        $userId = auth('api')->id();
        $address = $this->service->create($request->validated(), $userId);

        return ApiResponse::success(
            'Thêm địa chỉ mới thành công!',
            201,
            (new UserAddressResource($address))->toArray(request())
        );
    }

    public function update(UserAddressRequest $request, int $id)
    {
        $userId = auth('api')->id();
        $address = $this->service->update($request->validated(), $id, $userId);

        return ApiResponse::success(
            'Cập nhật địa chỉ thành công!',
            200,
            (new UserAddressResource($address))->toArray(request())
        );
    }

    public function destroy(int $id)
    {
        $userId = auth('api')->id();
        $this->service->delete($id, $userId);

        return ApiResponse::success('Xóa địa chỉ thành công!', 200);
    }

    public function setDefault(int $id)
    {
        $userId = auth('api')->id();
        $address = $this->service->setDefault($id, $userId);

        return ApiResponse::success(
            'Đặt địa chỉ mặc định thành công!',
            200,
            (new UserAddressResource($address))->toArray(request())
        );
    }

    // public function restore(int $id)
    // {
    //     $userId = auth('api')->id();
    //     $address = $this->service->restore($id, $userId);

    //     return ApiResponse::success(
    //         'Khôi phục địa chỉ thành công!',
    //         200,
    //         (new UserAddressResource($address))->toArray(request())
    //     );
    // }
}