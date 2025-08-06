<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class UserService
{
    public function getUsersByRole(array $roles)
    {
        try {
            return User::with('role')
                ->whereHas('role', fn($q) => $q->whereIn('name', $roles))
                ->get();
        } catch (\Exception $e) {
            logger('Log bug getUsersByRole', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw new ApiException('Không thể lấy danh sách người dùng!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(array $data)
    {
        DB::beginTransaction();
        try {
            $roleName = $data['role_name'];
            $rawPassword = $data['password'] ?? Str::random(8);

            if (!$this->checkCanManageRole($roleName)) {
                throw new ApiException('Bạn không có quyền tạo tài khoản với vai trò này!', Response::HTTP_FORBIDDEN);
            }

            if (request()->hasFile('image')) {
                $data['image'] = Common::uploadImageToCloudinary(request()->file('image'), 'egomall/avatars');
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($rawPassword),
                'role_id' => $this->getRoleIdByName($roleName),
                'is_active' => $data['is_active'] ?? true,
                'image' => $data['image'] ?? null,
            ]);

            DB::commit();

            // Gửi mail yêu cầu đặt lại mật khẩu
            try {
                Common::sendSetPasswordMail($user, $roleName);
            } catch (\Throwable $e) {
                logger()->error('Gửi mail thất bại sau khi tạo user', [
                    'user_id' => $user->id,
                    'error_message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]);
            }

            return $user;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug store user', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Không thể tạo người dùng!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function checkCanManageRole(string $targetRole): bool
    {
        $currentRole = auth('api')->user()->role->name;

        $allowed = match ($currentRole) {
            'super-admin' => ['admin', 'staff', 'customer'],
            'admin'       => ['staff', 'customer'],
            default       => []
        };

        return in_array($targetRole, $allowed);
    }

    protected function getRoleIdByName(string $roleName): int
    {
        return Role::where('name', $roleName)->value('id')
            ?? throw new ApiException('Vai trò không tồn tại!', Response::HTTP_BAD_REQUEST);
    }
}
