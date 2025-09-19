<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\RepayRequest;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function checkOutOrders(OrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();
            $user = auth('api')->user();

            $subtotal = 0;
            $total = 0;
            $totalDiscount = 0;
            $totalDiscountVoucher = 0;
            $totalFlashSale = 0;

            $shippingMethod = ShippingMethod::find($data['shipping_method_id'] ?? null);

            if (!$shippingMethod) {
                throw new ApiException('Phương thức vận chuyển không hợp lệ!', Response::HTTP_NOT_FOUND);
            }

            $shippingZone = ShippingZone::where('shipping_method_id', $shippingMethod->id)
                ->where('province_code', $data['province_code'] ?? null)
                ->where('is_available', true)
                ->first();

            if (!$shippingZone) {
                throw new ApiException('Phí vận chuyển không khả dụng cho khu vực này!', Response::HTTP_NOT_FOUND);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'unique_id' => '',
                'total_price' => 0,
                'status' => 'ordered',
                'payment_status' => 'unpaid',
                'note' => $data['note'],
                'shipping_name' => $data['shipping_name'],
                'shipping_phone' => $data['shipping_phone'],
                'shipping_email' => $data['shipping_email'],
                'shipping_address' => $data['shipping_address'],
                'payment_method' => $data['payment_method'],
                'shipping_fee' => $shippingZone->fee,
                'shipping_method_snapshot' => $shippingMethod->name,
            ]);
            $order->update(['unique_id' => Common::generateUniqueId($order->id)]);

            $variantIds = collect($data['orders'])->flatMap(fn($order) => collect($order['products'])->pluck('id'))->merge(
                collect($data['orders'])->flatMap(fn($order) => collect($order['gifts'] ?? [])->pluck('id'))
            )->unique();

            $variants = ProductVariant::with(['product', 'values'])
                ->whereIn('id', $variantIds)
                ->lockForUpdate() // Tránh tình trạng race condition
                ->get()
                ->keyBy('id');

            $allPromotions = Promotion::with(['products', 'productVariants', 'giftProduct.variants', 'giftProductVariant'])
                ->where('status', '!=', 0)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->get();

            $voucher = isset($data['voucher_id']) ? $this->checkVoucher($user->id, $data['voucher_id']) : null;

            foreach ($data['orders'] as $orderItem) {
                foreach ($orderItem['products'] as $productItem) {
                    $variant = $variants[$productItem['id']] ?? null;
                    if (!$variant) continue;

                    if ($variant->quantity == 0 || $productItem['quantity'] > $variant->quantity) {
                        $variantValue = $variant->values->map(
                            fn($v) => ($v->option->name ?? 'Thuộc tính') . ": " . $v->value
                        )->implode(' | ');
                        throw new ApiException("Sản phẩm {$variant->product->name} ({$variantValue}) không đủ hàng!!");
                    }

                    $promotions = $this->getApplicablePromotions($variant, $allPromotions);
                    $flashSale = $promotions['flash_sale'];

                    $priceOriginal = $variant->sale_price ?: $variant->price;
                    $lineItemTotal = $priceOriginal * $productItem['quantity'];
                    $subtotal += $lineItemTotal;

                    $discountFlashSale = 0;
                    if ($flashSale) {
                        $discountFlashSale = $flashSale->promotion_type === 'percentage'
                            ? $lineItemTotal * ($flashSale->discount_value / 100)
                            : $flashSale->discount_value;

                        $totalFlashSale += $discountFlashSale;
                        $totalDiscount += $discountFlashSale;
                    }

                    // Voucher discount chỉ tính trên tổng đơn
                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $productItem['quantity'],
                        'price' => $priceOriginal,
                        'sale_price' => $discountFlashSale,
                        'is_gift' => false
                    ]);
                }

                // Xử lý quà tặng
                foreach ($orderItem['gifts'] ?? [] as $gift) {
                    $giftVariant = $variants[$gift['id']] ?? null;
                    if (!$giftVariant) continue;

                    $giftPromotions = $this->getApplicablePromotions($giftVariant, $allPromotions);
                    if (!$giftPromotions['gift']) {
                        throw new ApiException("Quà tặng không thuộc chương trình này!!", 400);
                    }

                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_variant_id' => $giftVariant->id,
                        'quantity' => $gift['quantity'],
                        'price' => 0,
                        'sale_price' => 0,
                        'is_gift' => true
                    ]);
                }
            }

            // Tính tổng giảm giá từ voucher sau khi trừ flash sale
            if ($voucher) {
                $totalAfterFlashSale = $subtotal - $totalFlashSale;

                if ($voucher->min_order_value && $totalAfterFlashSale < $voucher->min_order_value) {
                    throw new ApiException("Đơn hàng chưa đạt giá trị tối thiểu để sử dụng voucher này!", Response::HTTP_BAD_REQUEST);
                }

                $voucherDiscount = $voucher->discount_type === 'percent'
                    ? $totalAfterFlashSale * ($voucher->discount_value / 100)
                    : $voucher->discount_value;

                if ($voucher->max_amount && $voucherDiscount > $voucher->max_amount) {
                    $voucherDiscount = $voucher->max_amount;
                }

                $totalDiscountVoucher = $voucherDiscount;
                $totalDiscount += $voucherDiscount;

                // Tính toán số lượng voucher (hoặc ghi số lần sủ dụng voucher nếu có giới discount_limit)
                $this->updateVoucherUsage($user->id, $voucher->id);
            }

            $total = $subtotal - $totalDiscount;
            $total += $shippingZone->fee;

            $order->update([
                'total_discount' => $totalDiscount,
                'total_price' => $total,
                'coupon_id' => $voucher->id ?? null,
                'discount_details' => [
                    'totalDiscountVoucher' => $totalDiscountVoucher,
                    'totalFlashSale' => $totalFlashSale
                ],
            ]);

            Common::calculateOrderStock($order);


            // Xử lý thanh toán theo phương thức được chọn
            $paymentResponse = $this->processPaymentByMethod($order);

            // Lưu đơn hàng vào database nếu thành công
            DB::commit();

            return $paymentResponse;

        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug check out orders', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!');
        }
    }


    public function cancelOrders(Request $request, $uniqueId)
    {
        DB::beginTransaction();
        try {
            $user = auth('api')->user();

            $order = Order::where('unique_id', $uniqueId)->lockForUpdate()->first();

            if (!$order) {
                throw new ApiException('Không tìm thấy đơn hàng!!', Response::HTTP_NOT_FOUND);
            }
            if ((int)$order->user_id !== (int)$user->id) {
                throw new ApiException('Bạn không có quyền hủy đơn này!', Response::HTTP_FORBIDDEN);
            }

            // Idempotent
            if ($order->status === 'cancelled') {
                return ApiResponse::success('Hủy đơn hàng thành công!', data: [
                    'order_id'       => $order->unique_id,
                    'status'         => $order->status,
                    'payment_status' => $order->payment_status,
                ]);
            }

            if ($order->status !== 'ordered') {
                throw new ApiException('Chỉ có thể hủy đơn hàng khi đang chờ xác nhận!', Response::HTTP_BAD_REQUEST);
            }

            // $gateway = $order->payment_method; // COD | MOMO | VNPAY
            $isPaid  = $order->payment_status === 'paid';
            // $amount  = (int) round($order->total_price);

            // 3) Ví điện tử (MoMo/VNPay)
            if (!$isPaid) {
                // Unpaid: hủy ngay, không refund

                $order->update([
                    'status'         => 'cancelled',
                    'payment_status' => 'cancelled',
                    'payment_date'   => now(),
                    'reason'  => $request->reason,
                ]);

                Common::restoreOrderStock($order);

                Common::revertVoucherUsageInline($order);

                Common::sendOrderStatusMail($order, 'cancelled');

                DB::commit();

                return ApiResponse::success('Hủy đơn hàng thành công!', data: [
                    'order_id'       => $order->unique_id,
                    'status'         => $order->status,
                ]);
            }

            // 4) Ví điện tử đã PAID -> refund ĐỒNG BỘ
            if (empty($order->transaction_id)) {
                throw new ApiException('Thiếu mã giao dịch, không thể hoàn tiền!', Response::HTTP_CONFLICT);
            }

            $response = $this->processRefundCancelledOrderByMethod($order, $request);

            DB::commit();

            return $response;
        } catch (ApiException $e) {
            DB::rollBack();
            // return ApiResponse::error($e->getMessage(), $e->getCode());
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            logger('Log bug cancel orders', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!');
        }
    }



    public function repay(RepayRequest $request, $uniqueId)
    {
        // $request->validate([
        //     'payment_method' => 'required|in:COD,MOMO,VNPAY',
        // ]);

        $user = auth('api')->user();

        $order = Order::where('unique_id', $uniqueId)->lockForUpdate()->first();
        if (!$order) {
            throw new ApiException('Không tìm thấy đơn hàng!', 404);
        }
        if ((int)$order->user_id !== (int)$user->id) {
            throw new ApiException('Bạn không có quyền thao tác đơn này!', 403);
        }
        if ($order->status !== 'ordered') {
            throw new ApiException('Chỉ hỗ trợ thanh toán lại khi đơn đang chờ xác nhận!', 400);
        }
        if ($order->payment_status !== 'unpaid') {
            throw new ApiException('Đơn hàng không ở trạng thái chưa thanh toán!', 400);
        }

        $order->update([
            'payment_method'    => $request->payment_method,
            'transaction_id'    => null,
            'payment_created_at' => null,
            'payment_date'      => null,
        ]);

        // logger('Order repay', [
        //     'order_id'       => $order->unique_id,
        //     'method'         => $order->payment_method,
        //     'user_id'        => $user->id,
        // ]);

        // Gọi lại flow tạo link theo phương thức mới
        return $this->processPaymentByMethod($order);
    }

    public function requestReturn(Request $request, string $uniqueId)
    {
        $reason = (string) $request->input('reason', '');

        try {
            $user = auth('api')->user();

            return DB::transaction(function () use ($uniqueId, $reason, $user) {
                // Khoá hàng để tránh race
                $order = Order::where('unique_id', $uniqueId)->lockForUpdate()->first();

                if (!$order) {
                    throw new ApiException('Không tìm thấy đơn hàng!', Response::HTTP_NOT_FOUND);
                }
                if ((int)$order->user_id !== (int)$user->id) {
                    throw new ApiException('Bạn không có quyền với đơn này!', Response::HTTP_FORBIDDEN);
                }

                // Điều kiện cho phép yêu cầu hoàn trả
                if ($order->status !== 'delivered') {
                    throw new ApiException('Chỉ được yêu cầu hoàn trả khi đơn đã hoàn tất!', Response::HTTP_BAD_REQUEST);
                }
                if ($order->payment_status !== 'paid') {
                    throw new ApiException('Đơn chưa thanh toán không thể hoàn trả!', Response::HTTP_BAD_REQUEST);
                }
                if (!$order->delivered_at || now()->diffInDays($order->delivered_at) > 7) {
                    throw new ApiException('Đã quá thời hạn 7 ngày kể từ khi giao hàng!', Response::HTTP_BAD_REQUEST);
                }

                // Idempotent: đã có trạng thái hoàn trả thì trả về luôn
                if (!is_null($order->return_status)) {
                    return ApiResponse::success('Yêu cầu hoàn trả đã tồn tại', Response::HTTP_CONFLICT, data: [
                        'order_id'      => $order->unique_id,
                        'return_status' => $order->return_status,
                    ]);
                }

                // Cập nhật yêu cầu
                $order->update([
                    'return_status'       => 'requested',
                    'reason'       => mb_substr($reason, 0, 255) ?: null,
                    'return_requested_at' => now(),
                    'status'              => 'return_sales',
                ]);

                // TODO (optional): bắn mail/thông báo cho admin tại đây

                return ApiResponse::success('Gửi yêu cầu hoàn trả thành công!',200, [
                    'order_id'      => $order->unique_id,
                    'return_status' => 'requested',
                ]);
            });
        } catch (ApiException $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode());
        } catch (\Throwable $e) {
            logger('Return request error', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!');
        }
    }

    private function processPaymentByMethod($order)
    {
        switch ($order->payment_method) {
            case 'COD':
                return app(CodController::class)->processPayment($order);
            case 'VNPAY':
                return app(VnpayController::class)->processPayment($order);
            case 'MOMO':
                return app(MomoController::class)->processPayment($order);
            case 'ZALOPAY':
                return app(ZaloPayController::class)->processPayment($order);
            case 'SEPAY':
                return app(SePayController::class)->processPayment($order);
        }
    }

    private function processRefundCancelledOrderByMethod($order, $request)
    {
        switch ($order->payment_method) {
            case 'COD':
                return app(CodController::class)->processRefundCancelOrderPayment($order, $request);
            case 'VNPAY':
                return app(VnPayController::class)->processRefundCancelOrderPayment($order,$request);
            case 'MOMO':
                return app(MoMoController::class)->processRefundCancelOrderPayment($order->transaction_id, $order->total_price,$request);
            case 'ZALOPAY':
                return app(ZaloPayController::class)->processRefundCancelOrderPayment($order, $request);
        }
    }

    private function checkPromotion($variant)
    {
        $now = now();
        $promotions = Promotion::with(['products', 'productVariants'])
            ->where('status', '!=', 0)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();

        $matchedPromotion = null;

        // 1. Nếu promotion áp dụng theo biến thể
        if ($promotions->productVariants->contains('id', $variant->id)) {
            $matchedPromotion = $promotions;
        }

        // 2. Nếu promotion áp dụng theo product cha
        if ($promotions->products->contains('id', $variant->product_id)) {
            $matchedPromotion = $promotions;
        }

        return $matchedPromotion;
    }

    private function checkVoucher($userId, $voucherId)
    {
        $now = now();
        $voucher = Coupon::with(['usages' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])
            ->where('id', $voucherId)
            ->where('status', '!=', 0)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();

        if (!$voucher) {
            throw new ApiException('Voucher không hợp lệ!!', Response::HTTP_NOT_FOUND);
        }

        // check số voucher toàn hệ thống, nếu = 0 hoặc < 0 thi throw exception
        if ($voucher->usage_limit <= 0) {
            throw new ApiException('Voucher đã được sử dụng hết số lượng cho phép!!', Response::HTTP_CONFLICT);
        }


        // check số lần mà user sài voucher, nếu lớn hơn số lần cho phép thi throw exception
        $usedVoucher = $voucher->usages->count();

        if ($voucher->discount_limit !== null && $usedVoucher >= $voucher->discount_limit) {
            throw new ApiException('Bạn đã sử dụng voucher này quá số lần cho phép!!', Response::HTTP_CONFLICT);
        }

        return $voucher;
    }

    private function updateVoucherUsage($userId, $voucherId)
    {
        $voucher = Coupon::find($voucherId);

        // Trừ số lượng voucher còn lại (nếu có giới hạn)
        if (!is_null($voucher->usage_limit) && $voucher->usage_limit > 0) {
            $voucher->decrement('usage_limit');
        }

        // Tạo mới 1 bản ghi usage nếu có set giới hạn discount_limit
        if (!is_null($voucher->discount_limit)) {
            $voucher->usages()->create([
                'user_id' => $userId,
            ]);
        }

        return true;
    }

    private function checkBuyAndGift($variantId, $promotion = null)
    {
        if (!$promotion || $promotion->promotion_type !== 'buy_get') {
            return false;
        }

        // Nếu promotion có gift_product_id → kiểm tra variantId có nằm trong danh sách variant của product đó không
        if ($promotion->gift_product_id && $promotion->giftProduct && $promotion->giftProduct->variants) {
            return $promotion->giftProduct->variants->contains('id', $variantId);
        }

        // Nếu promotion có gift_product_variant_id → kiểm tra trực tiếp
        if ($promotion->gift_product_variant_id) {
            return $promotion->gift_product_variant_id == $variantId;
        }

        return false;
    }

    private function getApplicablePromotions($variant, $promotions)
    {

        $matched = [
            'flash_sale' => null,
            'gift' => null,
        ];

        foreach ($promotions as $promotion) {
            // Áp dụng flash sale
            if (in_array($promotion->promotion_type, ['percentage', 'fixed_amount'])) {
                if (
                    $promotion->productVariants->contains('id', $variant->id) ||
                    $promotion->products->contains('id', $variant->product_id)
                ) {
                    $matched['flash_sale'] = $promotion;
                }
            }

            // Áp dụng quà tặng
            if ($promotion->promotion_type === 'buy_get') {
                $isGift = false;

                if ($promotion->gift_product_id && $promotion->giftProduct) {
                    $isGift = $promotion->giftProduct->variants->contains('id', $variant->id);
                }

                if ($promotion->gift_product_variant_id) {
                    $isGift = $promotion->gift_product_variant_id == $variant->id;
                }

                if ($isGift) {
                    $matched['gift'] = $promotion;
                }
            }
        }

        return $matched;
    }
}
