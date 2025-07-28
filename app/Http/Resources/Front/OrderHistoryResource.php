<?php

namespace App\Http\Resources\Front;

use App\Models\Promotion;
use App\Models\PromotionProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $deliveredAt = $this->delivered_at;

        // Xác định display_status
        if ($status === 'delivered' && !$this->review) {
            $displayStatus = 'Cần đánh giá';
        } else {
            $map = [
                'ordered'      => 'Chờ xác nhận',
                'confirmed'    => 'Đã xác nhận',
                'shipping'     => 'Đang giao',
                'delivered'    => 'Hoàn tất',
                'cancelled'    => 'Đã hủy',
                'return_sales' => 'Trả hàng',
            ];
            $displayStatus = $map[$status] ?? ucfirst($status);
        }

        return [
            'unique_id' => $this->unique_id,
            'status' => $status,
            'total_price' => $this->total_price,
            'total_discount' => $this->total_discount,
            'delivered_at' => optional($deliveredAt)->toDateTimeString(),
            'can_cancel' => $status === 'ordered',
            'can_review' => $status === 'delivered',
            'can_request_return' => $status === 'delivered' &&
                $deliveredAt &&
                now()->diffInDays($deliveredAt) <= 7,

            'note' => $this->note,
            'shipping_name' => $this->shipping_name,
            'shipping_phone' => $this->shipping_phone,
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date,
            'payment_status' => $this->payment_status,
            'address' => $this->shipping_address,
            'shipping_method_snapshot' => $this->shipping_method_snapshot,
            'shipping_fee' => $this->shipping_fee,

            'coupon' => $this->coupon ? [
                'code' => $this->coupon->code,
                'discount_type' => $this->coupon->discount_type,
                'discount_value' => $this->coupon->discount_value,
            ] : null,

            'products' => $this->details->map(function ($detail) {
                $variant = $detail->productVariant;
                $product = $variant?->product;

                $variantValues = $variant?->values->map(function ($v) {
                    return optional($v->option)->name . ': ' . $v->value;
                })->implode(' | ');

                // Mặc định = null
                $giftProduct = null;

                if ($detail->is_gift && $variant) {
                    $promotionIds = PromotionProduct::where('product_variant_id', $variant->id)
                        ->pluck('promotion_id');

                    $promotion = Promotion::whereIn('id', $promotionIds)
                        ->whereNotNull('gift_product_variant_id')
                        ->first();

                    if ($promotion && $promotion->giftProductVariant) {
                        $giftVariant = $promotion->giftProductVariant->loadMissing([
                            'product',
                            'values'
                        ]);

                        $giftProduct = [
                            'id'           => $giftVariant->id,
                            'sku'          => $giftVariant->sku,
                            'price'        => $giftVariant->price,
                            'sale_price'   => $giftVariant->sale_price,
                            'product_name' => $giftVariant->product->name ?? null,
                            'slug'         => $giftVariant->product->slug ?? null,
                            'image'        => $giftVariant->product->image ?? null,
                            'options'      => $giftVariant->values->map(function ($value) {
                                return [
                                    'name'  => $value->option->name ?? '',
                                    'value' => $value->value ?? ''
                                ];
                            })->values(),
                        ];
                    }
                }


                $isReviewed = $detail->review ? true : false;

                return [
                    'order_detail_id' => $detail->id,
                    'name'            => $product->name ?? 'Không rõ',
                    'image'           => $product->image ?? null,
                    'variant'         => $variantValues,
                    'quantity'        => $detail->quantity,
                    'price'           => $detail->price,
                    'sale_price'      => $detail->sale_price,
                    'is_gift'         => $detail->is_gift,
                    'is_gift_text'    => $detail->is_gift ? 'Quà tặng' : null,
                    'gift_product'    => $giftProduct,
                    'is_reviewed'     => $isReviewed,
                    'can_review'      => !$detail->is_gift && $this->status === 'delivered' && !$isReviewed,
                ];
            }),

        ];
    }
}
