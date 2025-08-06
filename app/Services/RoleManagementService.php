<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RoleManagementService
{

    /**
     * Lấy toàn bộ role trong hệ thống, loại trừ role đang đăng nhập
     */

    public function getAllRolesExceptCurrent()
    {
        try {
            $currentUser = auth('api')->user();

            if (!$currentUser || !$currentUser->role) {
                return null;
            }

            $currentRole = $currentUser->role->name;

            // Danh sách role bị loại trừ luôn
            $excludedRoles = ['customer', $currentRole];

            // Nếu không phải super-admin thì ẩn cả super-admin và admin (chỉ được tạo staff)
            if ($currentRole !== 'super-admin') {
                $excludedRoles[] = 'super-admin';
                $excludedRoles[] = 'admin';
            }

            $roles = Role::with(['permissions'])
                ->whereNotIn('name', $excludedRoles)
                ->get();

            return $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'perms' => $role->permissions->pluck('id')->toArray()
                ];
            });

        } catch (\Exception $e) {
            logger('Log bug get all roles', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy permission trong hệ thống
     */
    public function getAllSystemPermissions()
    {
        try {
            // lấy all permission trong hệ thống
            $permissionList = collect();

            $permissions = Permission::get();

            $permissions->each(fn($permission) => $permissionList->push([
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->display_name,
            ]));

            return $permissionList;
        } catch (\Exception $e) {
            logger('Log bug get all system permissions', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Tạo mới role và gán quyền cho role
     */

    public function storeRoleAndPermission($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            // Tạo role
            $role = Role::create([
                'name' => $data['role']['name'],
                'display_name' => $data['role']['display_name']
            ]);

            // Gán quyền cho role dc tạo
            $role->permissions()->sync($data['permissions']);

            DB::commit();

            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug store role and permission', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    /**
     * Update role và gán permissions cho role chỉ định
     */
    public function updateRoleAndPermissions($request, $roleId)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            // Tìm role đang update, nếu kh throw exception
            $role = Role::find($roleId);

            if (!$role) {
                throw new ApiException('Không tìm thấy vai trò này!!', Response::HTTP_NOT_FOUND);
            }

            // check nếu là role hệ thống thì k cho update role
            if ($role->is_system && isset($data['role']['name']) && $data['role']['name'] !== $role->name) {
                throw new ApiException('Không được cập nhật lại vai trò mặc định trong hệ thống!!', Response::HTTP_BAD_REQUEST);
            }

            // Cập nhật role
            $role->update([
                'name' => $data['role']['name'],
                'display_name' => $data['role']['display_name']
            ]);

            // Cập nhật permissions
            $role->permissions()->sync($data['permissions']);

            DB::commit();

            return $role;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            logger('Log bug assign permissions to role', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw new ApiException('Có lỗi xảy ra, vui lòng thử lại!!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
