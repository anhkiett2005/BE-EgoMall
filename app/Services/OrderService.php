<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Jobs\QueryZaloPayRefundJob;
use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;


class OrderService
{

    /**
     * Lấy toàn bộ danh sách  đơn hàng
     */
    public function modifyIndex()
    {
        try {
            $orders = Order::with(['user', 'details', 'coupon'])
                ->get();

            $listOrder = collect();

            $orders->each(function ($order) use ($listOrder) {
                $displayReturn = null;
                if ($order->status === 'return_sales') {
                    $map = [
                        'requested' => 'Yêu cầu hoàn trả',
                        'approved'  => 'Đã chấp nhận hoàn trả',
                        'completed' => 'Hoàn trả thành công',
                    ];
                    $displayReturn = $map[$order->return_status] ?? 'Trả hàng';
                }

                $listOrder->push([
                    'id' => $order->id,
                    'unique_id' => $order->unique_id,
                    'user' => $order->user->name,
                    'total_price' => $order->total_price,
                    'total_discount' => $order->total_discount,
                    'discount_details' => $order->discount_details,
                    'status' => $order->status,

                    'return_status'         => $order->return_status,
                    'display_return_status' => $displayReturn,
                    'return_requested_at'   => optional($order->return_requested_at)?->format('Y-m-d H:i:s'),
                    'return_note'           => $order->return_note,
                    'can_approve_return'    => $order->return_status === 'requested',
                    'can_reject_return'     => $order->return_status === 'requested',
                    'can_complete_return'   => $order->return_status === 'approved',

                    'note' => $order->note,
                    'shipping_name' => $order->shipping_name,
                    'shipping_phone' => $order->shipping_phone,
                    'shipping_email' => $order->shipping_email,
                    'shipping_address' => $order->shipping_address,
                    'shipping_method_snapshot' => $order->shipping_method_snapshot,
                    'shipping_fee' => $order->shipping_fee,
                    'reason' => $order->reason,
                    'voucher' => $order->coupon?->code,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'payment_date' => $order->payment_date,
                    'transaction_id' => $order->transaction_id,
                    'delivered_at' => optional($order->delivered_at)?->format('Y-m-d H:i:s'),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ]);
            });

            return $listOrder;
        } catch (\Exception $e) {
            logger('Log bug modify order', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }

    /**
     * Lấy chi tiết một đơn hàng
     */

    public function show(string $uniqueId)
    {
        try {
            $order = Order::with(['user', 'details', 'coupon'])
                ->where('unique_id', $uniqueId)
                ->first();

            // check nếu k có đơn hàng báo lỗi
            if (!$order) {
                throw new ApiException('Không tìm thấy đơn hàng!!', Response::HTTP_NOT_FOUND);
            }

            $displayReturn = null;
            if ($order->status === 'return_sales') {
                $map = [
                    'requested' => 'Yêu cầu hoàn trả',
                    'approved'  => 'Đã chấp nhận hoàn trả',
                    'completed' => 'Hoàn trả thành công',
                    'rejected'  => 'Từ chối hoàn trả',
                ];
                $displayReturn = $map[$order->return_status] ?? 'Trả hàng';
            }

            $orderDetail = collect();

            $orderDetail->push([
                'id' => $order->id,
                'unique_id' => $order->unique_id,
                'user' => $order->user->name,
                'total_price' => $order->total_price,
                'total_discount' => $order->total_discount,
                'discount_details' => $order->discount_details,
                'status' => $order->status,

                'return_status'         => $order->return_status,
                'display_return_status' => $displayReturn,
                'return_requested_at'   => optional($order->return_requested_at)?->format('Y-m-d H:i:s'),
                'return_note'           => $order->return_note,
                'can_approve_return'    => $order->return_status === 'requested',
                'can_reject_return'     => $order->return_status === 'requested',
                'can_complete_return'   => $order->return_status === 'approved',

                'note' => $order->note,
                'shipping_name' => $order->shipping_name,
                'shipping_phone' => $order->shipping_phone,
                'shipping_email' => $order->shipping_email,
                'shipping_address' => $order->shipping_address,
                'shipping_method_snapshot' => $order->shipping_method_snapshot,
                'shipping_fee' => $order->shipping_fee,
                'reason' => $order->reason,
                'voucher' => $order->coupon?->code,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'payment_date' => $order->payment_date,
                'transaction_id' => $order->transaction_id,
                'delivered_at' => optional($order->delivered_at)?->format('Y-m-d H:i:s'),
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            ]);
            return $orderDetail;
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            logger('Log bug show order', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }


    /**
     * Cập nhật status cho đơn hàng
     */
    public function updateStatus($request, $uniqueId)
    {
        DB::beginTransaction();
        try {
            $newStatus = $request->input('status');

            // Khóa bản ghi để tránh race-condition khi nhiều admin thao tác
            $order = Order::where('unique_id', $uniqueId)->lockForUpdate()->first();

            if (!$order) {
                throw new ApiException('Không tìm thấy đơn hàng!!', Response::HTTP_NOT_FOUND);
            }

            // Cập nhật trạng thái
            $order->status = $newStatus;

            // Set delivered_at MỘT LẦN khi lần đầu vào delivered
            // Set delivered_at MỘT LẦN khi lần đầu vào delivered
            if ($newStatus === 'delivered' && is_null($order->delivered_at)) {
                $order->delivered_at = now();
            }

            // // COD: giao thành công => coi như đã thu tiền
            // if (
            //     $newStatus === 'delivered'
            //     && $order->payment_method === 'COD'
            //     && $order->payment_status !== 'paid'
            // ) {
            //     $order->payment_status = 'paid';
            //     $order->payment_date   = now();
            // }

            // KHÔNG reset delivered_at nếu admin lỡ đổi ngược trạng thái
            // (cần giữ mốc giao hàng để tính SLA 7 ngày hoàn trả)

            $order->save();

            // Gửi mail cho các trạng thái cần gửi
            $mailStatusesToSend = ['ordered', 'confirmed', 'delivered'];
            if (in_array($newStatus, $mailStatusesToSend, true)) {
                Common::sendOrderStatusMail($order, $newStatus);
            }

            DB::commit();
            return $order;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            logger('Log bug update status order', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'error_trace'   => $e->getTraceAsString(),
            ]);

            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }


    public function approveReturn(string $uniqueId, ?string $note = null)
    {
        return DB::transaction(function () use ($uniqueId, $note) {
            $order = Order::where('unique_id', $uniqueId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new ApiException('Không tìm thấy đơn hàng!!', Response::HTTP_NOT_FOUND);
            }

            // Idempotent
            if ($order->return_status === 'approved') {
                return $order;
            }
            if ($order->return_status !== 'requested') {
                throw new ApiException('Trạng thái hoàn trả không hợp lệ để duyệt!', Response::HTTP_CONFLICT);
            }

            $order->update([
                'return_status' => 'approved',
                'status'        => 'return_sales',
                'return_note'   => $note ?? $order->return_note,
            ]);

            // Gửi mail sau commit để chắc chắn đọc được trạng thái mới
            Common::sendReturnApprovedMail($order, true);

            return $order;
        });
    }

    public function rejectReturn(string $uniqueId)
    {
        return DB::transaction(function () use ($uniqueId) {
            $order = Order::where('unique_id', $uniqueId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new ApiException('Không tìm thấy đơn hàng!!', Response::HTTP_NOT_FOUND);
            }

            if ($order->return_status === 'rejected') {
                // idempotent
                return $order;
            }
            if ($order->return_status !== 'requested') {
                throw new ApiException('Trạng thái hoàn trả không hợp lệ để từ chối!', Response::HTTP_CONFLICT);
            }

            $order->update([
                'return_status' => 'rejected',
                'status'        => 'delivered',
            ]);

            return $order;
        });
    }

    public function completeReturn(string $uniqueId)
    {
        return DB::transaction(function () use ($uniqueId) {
            $order = Order::where('unique_id', $uniqueId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new ApiException('Không tìm thấy đơn hàng!!', Response::HTTP_NOT_FOUND);
            }

            // Chỉ cho phép hoàn tất sau khi đã approve
            if ($order->return_status === 'completed') {
                // idempotent
                return $order;
            }
            if ($order->return_status !== 'approved') {
                throw new ApiException('Trạng thái hoàn trả không hợp lệ để hoàn tất!', Response::HTTP_CONFLICT);
            }

            // Chỉ hoàn tiền cho đơn đã thanh toán
            if ($order->payment_status !== 'paid') {
                // Nếu hệ thống của bạn cho phép return COD (chưa thu tiền online) vẫn “completed” không cần refund:
                // pass; (ở đây vẫn cho completed, nhưng payment_status không đổi)
                throw new ApiException('Đơn chưa thanh toán không thể hoàn trả!', Response::HTTP_BAD_REQUEST);
            }



            // 2) Refund (MVP: full total_price).
            //    - COD: không gọi API cổng thanh toán → coi là hoàn offline.
            //    - MOMO/VNPAY/ZALOPAY: gọi API hoàn tiền.

            if ($order->payment_status === 'paid') {
                $amount = (int) round($order->total_price);
                $update['return_status'] = 'completed';

                if ($order->payment_method === 'MOMO') {

                    if (empty($order->transaction_id)) {
                        throw new ApiException('Thiếu mã giao dịch để hoàn tiền MoMo!', Response::HTTP_CONFLICT);
                    }

                    $res = Common::refundMomoTransaction($order->transaction_id, $amount);

                    if ((int)($res['resultCode'] ?? -1) !== 0) {
                        logger('Log data refund', [
                            'response' => $res
                        ]);
                        throw new ApiException('Refund MoMo thất bại, vui lòng thử lại!', 502);
                    }

                    $refundTransId = $res['transId'];

                    $update['payment_status'] = 'refunded';
                    $update['payment_date']   = now(); // thời điểm refund thành công
                    $update['transaction_id'] = $refundTransId;


                } elseif ($order->payment_method === 'VNPAY') {

                    if (empty($order->transaction_id) || empty($order->payment_created_at)) {
                        throw new ApiException('Thiếu dữ liệu giao dịch để hoàn tiền VNPAY!', Response::HTTP_CONFLICT);
                    }

                    $params = [
                        'transaction_type' => '02', // full refund
                        'txn_ref'          => $order->unique_id,
                        'transaction_no'   => $order->transaction_id,
                        'amount'           => $amount,
                        'order_info'       => 'Hoàn tiền cho đơn trả hàng #' . $order->unique_id,
                        'create_by'        => auth('api')->user()->name ?? 'system',
                        'transaction_date' => optional($order->payment_created_at)->format('YmdHis'),
                    ];

                    $res = Common::refundVnPayTransaction($params);

                    if (!Common::validateSignatureFromJson($res)) {
                        throw new ApiException('Chữ ký không hợp lệ!', Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    if (($res['vnp_ResponseCode'] ?? '') !== '00') {
                        logger('Log data refund', [
                            'response' => $res
                        ]);
                        throw new ApiException('Refund VNPAY thất bại, vui lòng thử lại!', 502);
                    }

                    $refundTransId = $res['vnp_TransactionNo'];

                    $update['payment_status'] = 'refunded';
                    $update['payment_date']   = now(); // thời điểm refund thành công
                    $update['transaction_id'] = $refundTransId;
                }elseif($order->payment_method === 'ZALOPAY'){

                    if (empty($order->transaction_id)) {
                        throw new ApiException('Thiếu mã giao dịch để hoàn tiền ZaloPay!', Response::HTTP_CONFLICT);
                    }

                    $params = [
                        'zp_key1' => env('ZALO_PAY_KEY_1'),
                        'm_refund_id' => Carbon::now(config('app.timezone'))->format('ymd') .  '_'  . env('ZALO_PAY_APP_ID') . '_' . Str::random(10),
                        'app_id' => env('ZALO_PAY_APP_ID'),
                        'zp_trans_id' => $order->transaction_id,
                        'amount' => $order->total_price,
                        'timestamp' => Carbon::now(config('app.timezone'))->getTimestampMs(),
                        'description' => "Hoàn tiền cho đơn trả hàng #" . $order->unique_id,
                    ];

                    $res = Common::refundZaloPayTransaction($params);

                    $refundTransId = $res['refund_id'];

                    $update['payment_status'] = 'refund_processing';
                    $update['transaction_id'] = $refundTransId;

                    if($res['return_code'] == 3) {
                        // gọi queue query refund
                        $arrQueryParams = Arr::only($params, ['app_id', 'm_refund_id', 'timestamp','zp_key1']);

                        QueryZaloPayRefundJob::dispatch($order->id, $arrQueryParams)->delay(Carbon::now()->addSeconds(2));
                    }else if($res['return_code'] == 2) {
                        logger('Log data refund', [
                            'response' => $res
                        ]);

                        throw new ApiException('Refund ZALOPAY thất bại, vui lòng thử lại!', Response::HTTP_INTERNAL_SERVER_ERROR);
                    }


                }else {
                    // COD: không gọi API cổng thanh toán; bạn có thể xử lý hoàn offline ở nghiệp vụ kế toán
                    $refundTransId = Common::generateCodRefundTransactionId();

                    $update['payment_status'] = 'refunded';
                    $update['payment_date']   = now(); // thời điểm refund thành công
                    $update['transaction_id'] = $refundTransId;
                }
            }

            // 1) Cộng kho (đã nhận hàng trả về)
            Common::restoreOrderStock($order);

            // 3) Revert voucher (nếu muốn cho dùng lại)
            //    Tái dùng helper inline mà bạn đã viết trong Front\OrderController
            Common::revertVoucherUsageInline($order);

            // 4) Cập nhật đơn
            $order->update($update);

            // (optional) gửi mail thông báo hoàn tất hoàn trả

            return $order;
        });
    }
}
