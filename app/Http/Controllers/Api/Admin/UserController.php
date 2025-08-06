<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Response\ApiResponse;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

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

    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->store($request->validated());

        return ApiResponse::success('Tạo người dùng thành công!', 201, [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'image' => $user->image,
            'role' => $user->role->name,
            'is_active' => $user->is_active,
        ]);
    }
}
