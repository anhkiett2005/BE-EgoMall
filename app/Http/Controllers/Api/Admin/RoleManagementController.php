<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\StoreRoleAndPermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Requests\UpdateRoleAndPermissionRequest;
use App\Http\Resources\Admin\PermissionResource;
use App\Response\ApiResponse;
use App\Services\RoleManagementService;
use Illuminate\Http\JsonResponse;


class RoleManagementController extends Controller
{
    protected $roleManagementService;

    public function __construct(RoleManagementService $roleManagementService)
    {
        $this->roleManagementService = $roleManagementService;
    }


    public function getAllRoles()
    {
        $roles = $this->roleManagementService->getAllRolesExceptCurrent();

        return ApiResponse::success('Lấy danh sách vài trò thành công!!', data: $roles);
    }

    public function storeRoleAndPermission(StoreRoleAndPermissionRequest $request)
    {
        $role = $this->roleManagementService->storeRoleAndPermission($request);

        if ($role) {
            return ApiResponse::success('Tạo vai trò thành công!!', 200);
        }
    }

    public function destroyRole($roleId): JsonResponse
    {
        $this->roleManagementService->softDeleteRole($roleId);
        return ApiResponse::success('Xóa (mềm) vai trò thành công!!', 200);
    }

    public function restoreRole(int $roleId)
    {
        $role = $this->roleManagementService->restoreRole($roleId);
        return ApiResponse::success('Khôi phục vai trò thành công!', 200, data: [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
        ]);
    }


    public function assignPermissionsToRole(UpdateRoleAndPermissionRequest $request, $roleId)
    {
        $isRoleAssigned = $this->roleManagementService->updateRoleAndPermissions($request, $roleId);

        if ($isRoleAssigned) {
            return ApiResponse::success('Cập nhật vai trò với quyền thành công!!', 200);
        }
    }

    // Permissions
    public function getAllPermissions()
    {
        $permissions = $this->roleManagementService->getAllSystemPermissions();

        return ApiResponse::success('Lấy danh sách quyền thành công!!', data: $permissions);
    }

    public function storePermission(StorePermissionRequest $request)
    {
        $perm = $this->roleManagementService->createPermission($request->validated());
        return ApiResponse::success('Tạo permission thành công!', 200, (new PermissionResource($perm))->toArray(request()));
    }

    public function updatePermission(UpdatePermissionRequest $request, int $id)
    {
        $perm = $this->roleManagementService->updatePermission($id, $request->validated());
        return ApiResponse::success('Cập nhật permission thành công!', 200, (new PermissionResource($perm))->toArray(request()));
    }

    public function destroyPermission(int $id)
    {
        $this->roleManagementService->softDeletePermission($id);
        return ApiResponse::success('Xóa (mềm) permission thành công!', 200);
    }
}
