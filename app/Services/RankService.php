<?php
namespace App\Services;

use App\Classes\Common;
use App\Models\Rank;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class RankService {

    /**
     * Lấy toàn bộ danh sách ranks (frontend)
     */

    public function modifyIndex()
    {
        try {
            $ranks = Rank::select([
                'name',
                'amount_to_point',
                'min_spent_amount',
                'converted_amount',
                'discount',
                'maximum_discount_order',
                'minimum_point',
                'maintenance_point',
                'point_limit_transaction',
                'status_payment_point'
            ])->get();

            return $ranks;
        }catch (\Exception $e) {
            logger('Log bug modify ranks', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }

    /**
     * Lấy toàn bộ danh sách ranks (admin)
     */

    public function adminIndex()
    {
        try {
            $ranks = Rank::all();

            return $ranks;
        } catch (\Exception $e) {
            logger('Log bug admin ranks', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }

    /**
     * Tạo mới ranks
     */

    public function store($request)
    {

        $data = $request->all();
        DB::beginTransaction();

        try {

            // Tạo ranks
            foreach ($data['rankDetails'] as $rankDetail) {
                $rank = new Rank();
                $rank->name = $rankDetail['name'];
                $rank->image = isset($rankDetail['image']) ? $rankDetail['image'] : null;
                $rank->amount_to_point = $rankDetail['amount_to_point'];
                $rank->min_spent_amount = $rankDetail['min_spent_amount'];
                $rank->converted_amount = $rankDetail['converted_amount'];
                $rank->discount = $rankDetail['discount'] ?? null;
                $rank->maximum_discount_order = $rankDetail['maximum_discount_order'] ?? null;
                $rank->type_time_receive = $rankDetail['type_time_receive'] ?? null;
                $rank->time_receive_point = $rankDetail['time_receive_point'] ?? null;
                $rank->minimum_point = $rankDetail['minimum_point'] ?? null;
                $rank->maintenance_point = $rankDetail['maintenance_point'] ?? null;
                $rank->point_limit_transaction = $rankDetail['point_limit_transaction'] ?? null;
                $rank->status_payment_point = isset($rankDetail['status_payment_point']) ? $rankDetail['status_payment_point'] : 0;
                $rank->save();
            }

            DB::commit();

            return $rank;
        }catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug create ranks', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!!');
        }
    }
}
