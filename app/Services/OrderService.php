<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
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
                    'return_reason'         => $order->return_reason,
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
                    'cancel_reason' => $order->cancel_reason,
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
                'return_reason'         => $order->return_reason,
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
                'cancel_reason' => $order->cancel_reason,
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
                throw new ApiException('Không tìm thấy đơn hàng!!', \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
            }

            // Cập nhật trạng thái
            $order->status = $newStatus;

            // Set delivered_at MỘT LẦN khi lần đầu vào delivered
            if ($newStatus === 'delivered' && is_null($order->delivered_at)) {
                $order->delivered_at = now(); // lưu UTC, cast datetime sẽ lo hiển thị
            }

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


    public function approveReturn(string $uniqueId)
    {
        return DB::transaction(function () use ($uniqueId) {
            $order = Order::where('unique_id', $uniqueId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new ApiException('Không tìm thấy đơn hàng!!', Response::HTTP_NOT_FOUND);
            }

            // Chỉ cho approve khi user đã gửi yêu cầu
            if ($order->return_status === 'approved') {
                return $order;
            }
            if ($order->return_status !== 'requested') {
                throw new ApiException('Trạng thái hoàn trả không hợp lệ để duyệt!', Response::HTTP_CONFLICT);
            }

            $order->update([
                'return_status' => 'approved',
                'status'        => 'return_sales',
            ]);

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
            }

            // 1) Cộng kho (đã nhận hàng trả về)
            Common::restoreOrderStock($order);

            // 2) Refund (MVP: full total_price).
            //    - COD: không gọi API cổng thanh toán → coi là hoàn offline.
            //    - MOMO/VNPAY: gọi API hoàn tiền.
            $refundTransId = $order->transaction_id; // fallback giữ cũ nếu là COD

            if ($order->payment_status === 'paid') {
                $amount = (int) round($order->total_price);

                if ($order->payment_method === 'MOMO') {
                    if (empty($order->transaction_id)) {
                        throw new ApiException('Thiếu mã giao dịch để hoàn tiền MoMo!', Response::HTTP_CONFLICT);
                    }
                    $res = \App\Classes\Common::refundMomoTransaction($order->transaction_id, $amount);
                    if ((int)($res['resultCode'] ?? -1) !== 0) {
                        throw new ApiException('Refund MoMo thất bại, vui lòng thử lại!', 502);
                    }
                    $refundTransId = $res['transId'] ?? $order->transaction_id;
                } elseif ($order->payment_method === 'VNPAY') {
                    if (empty($order->transaction_id) || empty($order->payment_created_at)) {
                        throw new ApiException('Thiếu dữ liệu giao dịch để hoàn tiền VNPAY!', Response::HTTP_CONFLICT);
                    }
                    $params = [
                        'transaction_type' => '02', // full refund
                        'txn_ref'          => $order->unique_id,
                        'transaction_no'   => $order->transaction_id,
                        'amount'           => $amount,
                        'order_info'       => 'Hoàn tiền (return) đơn: ' . $order->unique_id,
                        'create_by'        => auth('api')->user()->name ?? 'system',
                        'transaction_date' => optional($order->payment_created_at)->format('YmdHis'),
                    ];
                    $resp = \App\Classes\Common::refundVnPayTransaction($params);
                    if (($resp['vnp_ResponseCode'] ?? '') !== '00') {
                        throw new ApiException('Refund VNPAY thất bại, vui lòng thử lại!', 502);
                    }
                    $refundTransId = $resp['vnp_TransactionNo'] ?? $order->transaction_id;
                } else {
                    // COD: không gọi API cổng thanh toán; bạn có thể xử lý hoàn offline ở nghiệp vụ kế toán
                }
            }

            // 3) Revert voucher (nếu muốn cho dùng lại)
            //    Tái dùng helper inline mà bạn đã viết trong Front\OrderController
            if (method_exists($this, 'revertVoucherUsageInline')) {
                // nếu bạn muốn tái sử dụng chung, cân nhắc đưa hàm này sang một Trait hoặc Common
                $this->revertVoucherUsageInline($order);
            } else {
                // Nếu chưa share được, tạm lặp code revert (khuyến nghị refactor sau):
                $coupon = \App\Models\Coupon::where('id', $order->coupon_id)->lockForUpdate()->first();
                if ($coupon) {
                    if (!is_null($coupon->usage_limit)) {
                        $coupon->increment('usage_limit');
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('coupon_usages', 'order_id')) {
                        \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                            ->where('user_id', $order->user_id)
                            ->where('order_id', $order->id)
                            ->delete();
                    } else {
                        $usage = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                            ->where('user_id', $order->user_id)
                            ->latest('id')->first();
                        if ($usage) $usage->delete();
                    }
                }
            }

            // 4) Cập nhật đơn
            $update = [
                'return_status' => 'completed',
                'status'        => 'return_sales',
            ];

            if ($order->payment_status === 'paid') {
                $update['payment_status'] = 'refunded';
                $update['payment_date']   = now(); // thời điểm refund thành công
                $update['transaction_id'] = $refundTransId; // lưu mã refund (theo cách bạn đang dùng)
            }

            $order->update($update);

            // (optional) gửi mail thông báo hoàn tất hoàn trả

            return $order;
        });
    }

    private function revertVoucherUsageInline(Order $order): void
    {
        if (!$order->coupon_id) return;

        // +1 lại usage_limit nếu có giới hạn
        $coupon = \App\Models\Coupon::where('id', $order->coupon_id)->lockForUpdate()->first();
        if ($coupon) {
            if (!is_null($coupon->usage_limit)) {
                $coupon->increment('usage_limit');
            }

            // Nếu bảng coupon_usages có order_id thì xóa đúng bản ghi của đơn này
            if (\Illuminate\Support\Facades\Schema::hasColumn('coupon_usages', 'order_id')) {
                \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $order->user_id)
                    ->where('order_id', $order->id)
                    ->delete();
            } else {
                // Fallback: xóa 1 usage gần nhất của user+coupon (kém chính xác hơn)
                $usage = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $order->user_id)
                    ->latest('id')->first();
                if ($usage) $usage->delete();
            }
        }
    }
}
