<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleAndPermissionRequest;
use App\Http\Requests\UpdateRoleAndPermissionRequest;
use App\Response\ApiResponse;
use App\Services\RoleManagementService;

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

        return ApiResponse::success('Lấy danh sách vài trò thành công!!',data: $roles);
    }

    public function getAllPermissions()
    {
        $permissions = $this->roleManagementService->getAllSystemPermissions();

        return ApiResponse::success('Lấy danh sách quyền thành công!!',data: $permissions);
    }

    public function storeRoleAndPermission(StoreRoleAndPermissionRequest $request)
    {
        $role = $this->roleManagementService->storeRoleAndPermission($request);

        if($role) {
            return ApiResponse::success('Tạo vai trò thành công!!');
        }
    }

    public function assignPermissionsToRole(UpdateRoleAndPermissionRequest $request, $roleId)
    {
        $isRoleAssigned = $this->roleManagementService->updateRoleAndPermissions($request, $roleId);

        if($isRoleAssigned) {
            return ApiResponse::success('Cập nhật vai trò với quyền thành công!!');
        }
    }
}
