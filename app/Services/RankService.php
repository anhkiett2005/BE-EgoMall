<?php
namespace App\Services;

use App\Classes\Common;
use App\Models\Rank;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

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
                $rank->amount_to_point = $rankDetail['amount_to_point'] ?? 0;
                $rank->min_spent_amount = $rankDetail['min_spent_amount'] ?? 0;
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

    /**
     * Cập nhật một rank
     */

    public function update($request, string $id)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {
            // tìm rank
            $rank = Rank::find($id);

            if (!$rank) {
                throw new ApiException('Không tìm thấy rank!!', Response::HTTP_NOT_FOUND);
            }

            // cập nhật rank
            $rank->update([
                'name' => $data['rankDetails'][0]['name'],
                'image' => isset($data['rankDetails'][0]['image']) ? $data['rankDetails'][0]['image'] : null,
                'amount_to_point' => $data['rankDetails'][0]['amount_to_point'],
                'min_spent_amount' => $data['rankDetails'][0]['min_spent_amount'],
                'converted_amount' => $data['rankDetails'][0]['converted_amount'],
                'discount' => $data['rankDetails'][0]['discount'] ?? null,
                'maximum_discount_order' => $data['rankDetails'][0]['maximum_discount_order'] ?? null,
                'type_time_receive' => $data['rankDetails'][0]['type_time_receive'] ?? null,
                'time_receive_point' => $data['rankDetails'][0]['time_receive_point'] ?? null,
                'minimum_point' => $data['rankDetails'][0]['minimum_point'] ?? null,
                'maintenance_point' => $data['rankDetails'][0]['maintenance_point'] ?? null,
                'point_limit_transaction' => $data['rankDetails'][0]['point_limit_transaction'] ?? null,
                'status_payment_point' => isset($data['rankDetails'][0]['status_payment_point']) ? $data['rankDetails'][0]['status_payment_point'] : 0,
            ]);

            DB::commit();

            return $rank;
        } catch(ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch(\Exception $e) {
            DB::rollBack();
            logger('Log bug update ranks', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!!');
        }
    }

    /**
     * Xóa một rank
     */

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            // Tìm rank
            $rank = Rank::find($id);

            if (!$rank) {
                throw new ApiException('Không tìm thấy rank!!', Response::HTTP_NOT_FOUND);
            }

            // Xóa rank
            $rank->delete();

            DB::commit();

            return $rank;
        } catch(ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch(\Exception $e) {
            DB::rollBack();
            logger('Log bug delete ranks', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!!');
        }
    }
}
