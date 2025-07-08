<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class UserAddressService
{
    public function listByUser(int $userId)
    {
        return UserAddress::with(['province', 'district', 'ward'])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function getById(int $id, int $userId): UserAddress
    {
        $address = UserAddress::with(['province', 'district', 'ward'])
            ->where('user_id', $userId)
            ->find($id);

        if (!$address) {
            throw new ApiException('Địa chỉ không tồn tại!', 404);
        }

        return $address;
    }

    public function create(array $data, int $userId): UserAddress
    {
        return DB::transaction(function () use ($data, $userId) {
            if ($data['is_default'] ?? false) {
                // Gán lại tất cả địa chỉ khác về false
                UserAddress::where('user_id', $userId)->update(['is_default' => false]);
            } else {
                // Nếu user chưa có địa chỉ mặc định nào => tự động gán cái mới là mặc định
                $hasDefault = UserAddress::where('user_id', $userId)->where('is_default', true)->exists();
                if (!$hasDefault) {
                    $data['is_default'] = true;
                }
            }

            $data['user_id'] = $userId;

            $address = UserAddress::create($data);

            if (!$address) {
                throw new ApiException('Thêm địa chỉ thất bại!', 500);
            }

            return $address;
        });
    }



    public function update(array $data, int $id, int $userId): UserAddress
    {
        return DB::transaction(function () use ($data, $id, $userId) {
            $address = $this->getById($id, $userId);

            if ($data['is_default'] ?? false) {
                UserAddress::where('user_id', $userId)->update(['is_default' => false]);
            }

            $updated = $address->update($data);

            if (!$updated) {
                throw new ApiException('Cập nhật địa chỉ thất bại!', 500);
            }

            return $address;
        });
    }


    public function delete(int $id, int $userId): void
    {
        $address = $this->getById($id, $userId);

        if (!$address->delete()) {
            throw new ApiException('Xóa địa chỉ thất bại!', 500);
        }

        if ($address->is_default) {
            $newDefault = UserAddress::where('user_id', $userId)
                ->whereNull('deleted_at')
                ->first();

            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }
    }


    public function setDefault(int $id, int $userId): UserAddress
    {
        $address = $this->getById($id, $userId);

        return DB::transaction(function () use ($userId, $address) {
            UserAddress::where('user_id', $userId)->update(['is_default' => false]);

            $updated = $address->update(['is_default' => true]);

            if (!$updated) {
                throw new ApiException('Đặt địa chỉ mặc định thất bại!', 500);
            }

            return $address;
        });
    }

    // public function restore(int $id, int $userId): UserAddress
    // {
    //     // Chỉ lấy bản ghi đã bị xóa
    //     $address = UserAddress::withTrashed()
    //         ->where('user_id', $userId)
    //         ->where('id', $id)
    //         ->first();

    //     if (!$address) {
    //         throw new ApiException('Không tìm thấy địa chỉ đã xóa!', 404);
    //     }

    //     if (!$address->trashed()) {
    //         throw new ApiException('Địa chỉ này chưa bị xóa!', 422);
    //     }

    //     return DB::transaction(function () use ($address, $userId) {
    //         $restored = $address->restore();

    //         if (!$restored) {
    //             throw new ApiException('Khôi phục địa chỉ thất bại!', 500);
    //         }

    //         // Nếu user không có địa chỉ mặc định nào, thì gán cái này
    //         $hasDefault = UserAddress::where('user_id', $userId)->where('is_default', true)->exists();
    //         if (!$hasDefault) {
    //             $address->update(['is_default' => true]);
    //         }

    //         return $address;
    //     });
    // }
}
