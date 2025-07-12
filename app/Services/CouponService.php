<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;

class CouponService
{
    public function list()
    {
        return Coupon::latest()->get();
    }

    public function getById(int $id): Coupon
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            throw new ApiException('Không tìm thấy mã giảm giá!', 404);
        }

        return $coupon;
    }

    public function store(array $data): Coupon
    {
        if ($data['discount_type'] === 'amount') {
            $data['max_discount'] = null;
        }

        return DB::transaction(fn() => Coupon::create($data));
    }

    public function update(int $id, array $data): Coupon
    {
        $coupon = $this->getById($id);

        if ($data['discount_type'] === 'amount') {
            $data['max_discount'] = null;
        }

        return DB::transaction(function () use ($coupon, $data) {
            $coupon->update($data);
            return $coupon;
        });
    }


    public function delete(int $id): void
    {
        $coupon = $this->getById($id);

        if ($coupon->trashed()) {
            throw new ApiException('Mã giảm giá đã bị xoá trước đó!');
        }

        $coupon->delete();
    }

    public function restore(int $id): Coupon
    {
        $coupon = Coupon::withTrashed()->find($id);

        if (!$coupon) {
            throw new ApiException('Không tìm thấy mã giảm giá đã xoá!', 404);
        }

        if (!$coupon->trashed()) {
            throw new ApiException('Mã giảm giá chưa bị xoá nên không cần khôi phục!');
        }

        $coupon->restore();
        return $coupon;
    }
}