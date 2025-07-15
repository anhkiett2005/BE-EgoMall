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
            'display_status' => $displayStatus,
            'total_price' => $this->total_price,
            'total_discount' => $this->total_discount,
            'delivered_at' => optional($deliveredAt)->toDateTimeString(),
            'can_cancel' => $status === 'ordered',
            'can_review' => $status === 'delivered',
            'can_request_return' => $status === 'delivered' &&
                $deliveredAt &&
                now()->diffInDays($deliveredAt) <= 7,

            'products' => $this->details->map(function ($detail) {
                $variant = $detail->productVariant;
                $product = $variant?->product;

                $variantValues = $variant?->values->map(function ($v) {
                    return optional($v->variantValue->option)->name . ': ' . $v->variantValue->value;
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
                            'values.variantValue.option'
                        ]);

                        $giftProduct = [
                            'id'         => $giftVariant->id,
                            'sku'        => $giftVariant->sku,
                            'price'      => $giftVariant->price,
                            'sale_price' => $giftVariant->sale_price,
                            'product_name' => $giftVariant->product->name ?? null,
                            'slug'         => $giftVariant->product->slug ?? null,
                            'image'        => $giftVariant->product->image ?? null,
                            'options'      => $giftVariant->values->map(function ($value) {
                                return [
                                    'name'  => $value->variantValue->option->name ?? '',
                                    'value' => $value->variantValue->value ?? ''
                                ];
                            })->values(),
                        ];
                    }
                }

                return [
                    'name' => $product->name ?? 'Không rõ',
                    'variant' => $variantValues,
                    'quantity' => $detail->quantity,
                    'price' => $detail->price,
                    'sale_price' => $detail->sale_price,
                    'is_gift' => $detail->is_gift,
                    'is_gift_text' => $detail->is_gift ? 'Quà tặng' : null,
                    'gift_product' => $giftProduct
                ];
            }),
        ];
    }
}