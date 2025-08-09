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
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserService
{
    public function getUsersByRole(array $roles)
    {
        try {
            return User::query()
                ->select(['id', 'name', 'email', 'phone', 'email_verified_at', 'is_active', 'created_at', 'updated_at', 'role_id'])
                ->with(['role:id,name,display_name'])
                ->whereHas('role', fn($q) => $q->whereIn('name', $roles))
                ->orderByDesc('created_at') // hoặc theo name tuỳ bạn
                ->get();
        } catch (\Exception $e) {
            logger()->error('Log bug getUsersByRole', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString()
            ]);
            throw new ApiException('Không thể lấy danh sách người dùng!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function findById(int $id): User
    {
        try {
            $user = User::with('role')->find($id);

            if (!$user) {
                throw new ApiException('Người dùng không tồn tại!', Response::HTTP_NOT_FOUND);
            }

            $targetRoleName = $user->role->name;

            if (!$this->checkCanManageRole($targetRoleName)) {
                throw new ApiException('Bạn không có quyền xem người dùng này!', Response::HTTP_FORBIDDEN);
            }

            return $user;
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            logger('Log bug find user by id', [
                'user_id' => $id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Không thể lấy chi tiết người dùng!', Response::HTTP_INTERNAL_SERVER_ERROR);
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

    public function update(array $data, int $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);

            $targetRoleName = $user->role->name;
            if (!$this->checkCanManageRole($targetRoleName)) {
                throw new ApiException('Bạn không có quyền cập nhật người dùng này!', Response::HTTP_FORBIDDEN);
            }

            // Nếu có cập nhật role
            if (!empty($data['role_name']) && $data['role_name'] !== $targetRoleName) {
                if (!$this->checkCanManageRole($data['role_name'])) {
                    throw new ApiException('Không có quyền gán vai trò này!', Response::HTTP_FORBIDDEN);
                }

                $user->role_id = $this->getRoleIdByName($data['role_name']);
            }

            // Xử lý ảnh đại diện
            if (request()->hasFile('image')) {
                if (!empty($user->image)) {
                    $publicId = Common::getCloudinaryPublicIdFromUrl($user->image);
                    if ($publicId) {
                        Common::deleteImageFromCloudinary($publicId);
                    }
                }

                $data['image'] = Common::uploadImageToCloudinary(
                    request()->file('image'),
                    'egomall/avatars'
                );
            }

            $user->name = $data['name'] ?? $user->name;
            $user->phone = $data['phone'] ?? $user->phone;
            $user->is_active = $data['is_active'] ?? $user->is_active;
            $user->image = $data['image'] ?? $user->image;

            $user->save();

            DB::commit();

            return $user->load('role');
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug update user', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Không thể cập nhật người dùng!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function updateStatus(int $id, bool $status)
    {
        DB::beginTransaction();
        try {
            $user = User::with('role')->findOrFail($id);

            $targetRoleName = $user->role->name;
            if (!$this->checkCanManageRole($targetRoleName)) {
                throw new ApiException('Bạn không có quyền cập nhật trạng thái người dùng này!', Response::HTTP_FORBIDDEN);
            }

            $user->is_active = $status;
            $user->save();

            DB::commit();
            return $user->load('role');
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug update status user', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Không thể cập nhật trạng thái người dùng!', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function checkCanManageRole(string $targetRole): bool
    {
        $currentRole = auth('api')->user()->role->name;

        $allowed = match ($currentRole) {
            'super-admin' => ['admin', 'staff', 'customer'],
            'admin'       => ['staff', 'customer'],
            'staff'       => ['customer'],
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
