<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductVariant;
use App\Models\Promotion;
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

        // Tạo đơn hàng trước
        $order = Order::create([
            'user_id' => $user->id,
            'unique_id' => '',
            'total_price' => 0,
            'status' => 'ordered',
            'payment_status' => 'pending',
            'note' => $data['note'],
            'shipping_name' => $data['shipping_name'],
            'shipping_phone' => $data['shipping_phone'],
            'shipping_email' => $data['shipping_email'],
            'shipping_address' => $data['shipping_address'],
            'payment_method' => $data['payment_method'],
        ]);

        $order->update(['unique_id' => Common::generateUniqueId($order->id)]);

        // Tối ưu load variant
        $variantIds = collect($data['orders'])->flatMap(fn($order) => collect($order['products'])->pluck('id'))->unique();
        $variants = ProductVariant::with(['product', 'values.variantValue.option'])->whereIn('id', $variantIds)->get()->keyBy('id');

        // Tối ưu promotion và voucher
        $voucher = isset($data['voucher_id']) ? $this->checkVoucher($data['voucher_id']) : null;
        $buyGetPromotion = Promotion::with(['giftProduct.variants', 'giftProductVariant'])
                                    ->where('status', '!=', 0)
                                    ->where('promotion_type', 'buy_get')
                                    ->where('start_date', '<=', now())
                                    ->where('end_date', '>=', now())
                                    ->first();

        foreach($data['orders'] as $orderItem) {
            foreach($orderItem['products'] as $productItem) {
                $variant = $variants[$productItem['id']] ?? null;
                if (!$variant) continue;

                if($variant->quantity == 0) {
                    $variantValue = $variant->values->map(function ($v) {
                        $name = $v->variantValue->option->name ?? 'Thuộc tính';
                        $value = $v->variantValue->value;
                        return "{$name}: {$value}";
                    })->implode(' | ');
                    throw new ApiException("Sản phẩm {$variant->product->name} ({$variantValue}) đã hết hàng!!");
                }

                if ($productItem['quantity'] > $variant->quantity) {
                    $variantValue = $variant->values->map(function ($v) {
                        $name = $v->variantValue->option->name ?? 'Thuộc tính';
                        $value = $v->variantValue->value;
                        return "{$name}: {$value}";
                    })->implode(' | ');
                    throw new ApiException("Sản phẩm {$variant->product->name} ({$variantValue}) chỉ còn {$variant->quantity}");
                }

                    $promotion = $this->checkPromotion($variant);
                    $priceOriginal = $variant->sale_price ? $variant->sale_price : $variant->price;
                    $lineItemTotal = $priceOriginal * $productItem['quantity'];
                    $subtotal += $lineItemTotal;

                    $discountFlashSale = 0;
                    if ($promotion) {
                        $discountFlashSale = $promotion->promotion_type == 'percentage'
                            ? $lineItemTotal * ($promotion->discount_value / 100)
                            : $promotion->discount_value;
                        $totalFlashSale += $discountFlashSale;
                        $totalDiscount += $discountFlashSale;
                    }

                    $voucherDiscount = 0;
                    if ($voucher) {
                        $totalAfterFlashSale = $subtotal - $totalFlashSale;
                        $voucherDiscount = $voucher->discount_type == 'percent'
                            ? $totalAfterFlashSale * ($voucher->discount_value / 100)
                            : $voucher->discount_value;

                        // Nếu có giới hạn tối đa và vượt quá → lấy max_amount
                        if ($voucher->max_amount && $voucherDiscount > $voucher->max_amount) {
                            $voucherDiscount = $voucher->max_amount;
                        }

                        $totalDiscountVoucher += $voucherDiscount;
                        $totalDiscount += $voucherDiscount;
                    }

                    // Tạo OrderDetail cho sản phẩm chính
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
                    if (!empty($orderItem['gifts'])) {
                        foreach ($orderItem['gifts'] as $gift) {
                            if ($this->checkBuyAndGift($gift['id'], $buyGetPromotion)) {
                                OrderDetail::create([
                                    'order_id' => $order->id,
                                    'product_variant_id' => $gift['id'],
                                    'quantity' => $gift['quantity'],
                                    'price' => 0,
                                    'sale_price' => 0,
                                    'is_gift' => true
                                ]);
                            } else {
                                throw new ApiException("Quà tặng không thuộc chương trình này!!", 400);
                            }
                        }
                    }
            }

            $total += $subtotal - $totalDiscount;
            $order->update([
                'total_discount' => $totalDiscount,
                'total_price' => $total,
                'coupon_id' => $voucher ? $voucher->id : null,
                'discount_details' => [
                    'totalDiscountVoucher' => $totalDiscountVoucher,
                    'totalFlashSale' => $totalFlashSale
                ]
            ]);

            DB::commit();

            return ApiResponse::success('Đơn hàng đã được tạo thành công!!', Response::HTTP_CREATED);
        } catch (ApiException $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage(), $e->getCode(), $e->getErrors());
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

    private function checkVoucher($voucherId)
    {
        $now = now();
        $voucher = Coupon::where('id', $voucherId)
                         ->where('status', '!=', 0)
                         ->where('start_date', '<=', $now)
                         ->where('end_date', '>=', $now)
                         ->first();

        if(!$voucher) {
            throw new ApiException('Voucher không hợp lệ!!', Response::HTTP_NOT_FOUND);
        }

        return $voucher;
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


}
