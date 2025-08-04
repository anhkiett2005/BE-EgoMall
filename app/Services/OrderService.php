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
                $listOrder->push([
                    'id' => $order->id,
                    'unique_id' => $order->unique_id,
                    'user' => $order->user->name,
                    'total_price' => $order->total_price,
                    'total_discount' => $order->total_discount,
                    'discount_details' => $order->discount_details,
                    'status' => $order->status,
                    'mail_status' => $order->mail_status,
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

            $orderDetail = collect();

            $orderDetail->push([
                'id' => $order->id,
                'unique_id' => $order->unique_id,
                'user' => $order->user->name,
                'total_price' => $order->total_price,
                'total_discount' => $order->total_discount,
                'discount_details' => $order->discount_details,
                'status' => $order->status,
                'mail_status' => $order->mail_status,
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
            $data = $request->all();
            $newStatus = $data['status'];

            $order = Order::where('unique_id', $uniqueId)->first();

            if (!$order) {
                throw new ApiException('Không tìm thấy đơn hàng!!', Response::HTTP_NOT_FOUND);
            }

            $order->update([
                'status' => $newStatus,
            ]);

            // Gửi mail nếu là các trạng thái cần gửi
            $mailStatusesToSend = ['ordered', 'confirmed', 'delivered'];

            if (in_array($newStatus, $mailStatusesToSend)) {
                Common::sendOrderStatusMail($order, $newStatus);
            }

            DB::commit();
            return $order;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug update status order', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            throw new ApiException('Có lỗi xảy ra!!', 500);
        }
    }
}
