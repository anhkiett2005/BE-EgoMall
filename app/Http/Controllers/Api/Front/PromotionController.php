<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\OrderDetail;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function getPromotionMap()
    {
        try {
            $promotionMap = collect();

            // Lấy danh sách khuyến mãi
            $promotions = Promotion::with(['products','productVariants'])
                                ->where('start_date','<=', Carbon::now())
                                ->where('end_date','>=', Carbon::now())
                                ->where('status','!=',0)
                                ->get();

            // Thu thập toàn bộ variant_id có liên quan tới promotion
            $promotionVariantIds = $promotions->flatMap(function ($promotion) {
                return $promotion->productVariants->pluck('id')
                    ->merge(
                        $promotion->products->flatMap(function ($product) {
                            return $product->variants->pluck('id');
                        })
                    );
            })
            ->unique();

            // Tính sold quantity một lần cho toàn bộ variant
            $soldQuantities = OrderDetail::whereIn('product_variant_id', $promotionVariantIds)
                                        ->select('product_variant_id', DB::raw('SUM(quantity) as total'))
                                        ->groupBy('product_variant_id')
                                        ->pluck('total', 'product_variant_id'); // [variant_id => quantity]


            foreach($promotions as $promotion) {
                // Tính thời gian còn lại của promotion
                $promotionEndDate = $promotion->end_date;
                $end = Carbon::parse($promotionEndDate);
                $diffInHours = $end->toIso8601String();

                // Trường hợp áp dụng toàn bộ sản phẩm khi có product_id
                if(!empty($promotion->products)) {
                    foreach($promotion->products as $product) {
                        // Lấy tất cả variant của product
                        $variantIds = $product->variants->pluck('id');

                        // Lấy tổng số lượng bán ra khi có product_id
                        $soldQuantity = $variantIds->sum(function ($variantId) use ($soldQuantities) {
                            return $soldQuantities[$variantId] ?? 0;
                        });

                        $promotionMap->put("product_" . $product->id, [
                            'type' => 'product',
                            'productId' => $product->id,
                            'promotionId' => $promotion->id,
                            'promotionName' => $promotion->name,
                            'endDate' => $diffInHours,
                            'conditions' => $this->getPromotionConditions($promotion),
                            'soldQuantity' => $soldQuantity
                        ]);
                    }
                }

                // Trường hợp áp dụng cho sản phẩm biến thể khi có product_variant_id
                if(!empty($promotion->productVariants)) {
                    foreach($promotion->productVariants as $variant) {
                        $soldQuantity = $soldQuantities[$variant->id] ?? 0;
                        $promotionMap->put("variant_" . $variant->id, [
                            'type' => 'variant',
                            'variantId' => $variant->id,
                            'parentProduct' => $variant->product_id,
                            'promotionId' => $promotion->id,
                            'promotionName' => $promotion->name,
                            'endDate' => $diffInHours,
                            'conditions' => $this->getPromotionConditions($promotion),
                            'soldQuantity' => $soldQuantity
                        ]);
                    }
                }
            }

            return ApiResponse::success('Data fetched successfully',data: $promotionMap);
        }catch(\Exception $e) {
            logger('Log bug',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
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
