<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PromotionController extends Controller
{
    public function getPromotionMap()
    {

        $promotionMap = collect();

        // Lấy danh sách khuyến mãi
        $promotions = Promotion::with(['products','productVariants'])
                              ->where('start_date','<=', Carbon::now())
                              ->where('end_date','>=', Carbon::now())
                              ->where('status','!=',0)
                              ->get();

        foreach($promotions as $promotion) {
            // Tính thời gian còn lại của promotion
            $promotionEndDate = $promotion->end_date;
            $end = Carbon::parse($promotionEndDate);
            $diffInHours = $end->toIso8601String();

            // Trường hợp áp dụng toàn bộ sản phẩm khi có product_id
            if($promotion->products()->exists()) {
                foreach($promotion->products as $product) {
                    $promotionMap->put($product->id, [
                        'type' => 'product',
                        'productId' => $product->id,
                        'promotionId' => $promotion->id,
                        'promotionName' => $promotion->name,
                        'endDate' => $diffInHours,
                        'conditions' => $this->getPromotionConditions($promotion)
                    ]);
                }
            }

            // Trường hợp áp dụng cho sản phẩm biến thể khi có product_variant_id
            if($promotion->productVariants()->exists()) {
                foreach($promotion->productVariants as $variant) {
                    $promotionMap->put($variant->id, [
                        'type' => 'variant',
                        'variantId' => $variant->id,
                        'parentProduct' => $variant->product_id,
                        'promotionId' => $promotion->id,
                        'promotionName' => $promotion->name,
                        'endDate' => $diffInHours,
                        'conditions' => $this->getPromotionConditions($promotion)
                    ]);
                }
            }
        }

        return ApiResponse::success('Data fetched successfully',data: $promotionMap);
    }

    protected function getPromotionConditions($promotion)
    {
        $conditions = [
            'type' => $promotion->promotion_type
        ];

        if($promotion->promotion_type == 'buy_get') {
            $conditions['buyQuantity'] = $promotion->buy_quantity;
            $conditions['getQuantity'] = $promotion->get_quantity;

            // check nếu quà tặng là sản phẩm biến thể thì thêm product_variant_id
            if($promotion->gift_product_variant_id) {
                $conditions['giftType'] = 'variant';
                $conditions['giftProductVariantId'] = $promotion->gift_product_variant_id;
                $conditions['parentProductId'] = optional(ProductVariant::find($promotion->gift_product_variant_id))->product_id;
            }else {
                $conditions['giftType'] = 'product all';
                $conditions['giftProductId'] = $promotion->gift_product_id;
            }
        }

        if(in_array($promotion->promotion_type, ['percentage', 'fixed_amount'])) {
            $conditions['discountType'] = $promotion->discount_type;
            $conditions['discountValue'] = $promotion->discount_value;
        }

        return $conditions;
    }
}
