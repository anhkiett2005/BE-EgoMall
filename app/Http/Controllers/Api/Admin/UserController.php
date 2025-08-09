<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Response\ApiResponse;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function listAdmins(): JsonResponse
    {
        $users = $this->userService->getUsersByRole(['admin']);
        return ApiResponse::success('Lấy danh sách admin thành công', 200, UserResource::collection($users)->toArray(request()));
    }

    public function listStaffs(): JsonResponse
    {
        $users = $this->userService->getUsersByRole(['staff']);
        return ApiResponse::success('Lấy danh sách nhân viên thành công', 200, UserResource::collection($users)->toArray(request()));
    }

    public function listCustomers(): JsonResponse
    {
        $users = $this->userService->getUsersByRole(['customer']);
        return ApiResponse::success('Lấy danh sách khách hàng thành công', 200, UserResource::collection($users)->toArray(request()));
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        return ApiResponse::success('Lấy chi tiết người dùng thành công!', 200, (new UserResource($user))->toArray(request()));
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->store($request->validated());
        return ApiResponse::success('Tạo người dùng thành công!', 200, (new UserResource($user))->toArray($request));
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->update($request->validated(), $id);
        return ApiResponse::success('Cập nhật người dùng thành công!', 200, (new UserResource($user))->toArray($request));
    }

    public function updateStatus(UpdateUserStatusRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->updateStatus($id, $request->is_active);
        return ApiResponse::success('Cập nhật trạng thái người dùng thành công!', 200, (new UserResource($user))->toArray($request));
    }
}