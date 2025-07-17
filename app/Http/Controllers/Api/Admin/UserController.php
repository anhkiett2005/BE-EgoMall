<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Response\ApiResponse;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = $this->userService->modifyIndex();

        return ApiResponse::success('Lấy danh sách người dùng thành công!!', data: $users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = $this->userService->show($id);

            if ($user) {
                return ApiResponse::success('Lấy chi tiết người dùng thành công!!', data: $user);
            }
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
        }
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(UpdateUserStatusRequest $request, string $id)
    {
        try {
            $user = $this->userService->update($request, $id);
            return ApiResponse::success('Cập nhật trạng thái người dùng thành công!', data: $user);
        } catch (ApiException $e) {
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
