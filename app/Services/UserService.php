<?php
namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UserService {

    /**
     * Lấy toàn bộ danh sách users
     */

    public function modifyIndex()
    {
        try {
            // Lấy all users trong hệ thống
            $users = User::with(['role'])
                         ->get();

            // Xử lý dữ liệu
            $listUser = collect();

            $users->each(function ($user) use ($listUser) {
                $listUser->push([
                    'id' => $user->id,
                    'name' => $user->email,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at?->format('d-m-Y H:i:s'),
                    'image' => $user->image,
                    'role' => $user->role->id,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->format('d-m-Y H:i:s'),
                    'updated_at' => $user->updated_at->format('d-m-Y H:i:s'),
                ]);
            });

            return $listUser;
        } catch (\Exception $e) {
            logger('Log bug modify user', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }

    /**
     * Lấy chi tiết 1 users
     */
    public function show(string $id)
    {
        try {
            // Tìm user
            $user = User::with(['role'])
                        ->where('id', '=', $id)
                        ->first();

            if (!$user) {
                throw new ApiException('Không tìm thấy tài khoản trong hệ thống!!', 404);
            }

            $userDetail = collect();

            $userDetail->push([
                'id' => $user->id,
                'name' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at?->format('d-m-Y H:i:s'),
                'image' => $user->image,
                'role' => $user->role->id,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at->format('d-m-Y H:i:s'),
                'updated_at' => $user->updated_at->format('d-m-Y H:i:s'),
            ]);

            return $userDetail;
        } catch(ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            logger('Log bug show user', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }

    /**
     * Cập nhật một User
     */
    public function update($request, string $id)
    {
        DB::beginTransaction();
        try {
            // Tìm user để update
            $user = User::with(['role'])
                        ->find($id);

            if(!$user) {
                throw new ApiException('Không tìm thấy tài khoản trong hệ thống!!', Response::HTTP_NOT_FOUND);
            }
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
            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }
}
