<?php
namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Promotion;
use Exception;

class PromotionServices {

    /**
     * Lấy toàn bộ danh sách promotions
     */

    public function modifyIndex()
    {
        try {
            $promotions = Promotion::with(['products', 'productVariants', 'giftProduct', 'giftProductVariant'])
                                   ->get();

            // xử lý và trả về danh sách promotions
            $listPromotions = $promotions->map(function ($promotion) {
                $data = [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'promotion_type' => $promotion->promotion_type,
                    'start_date' => $promotion->start_date,
                    'end_date' => $promotion->end_date,
                    'status' => $promotion->status ? 'active' : 'inactive',
                    'created_at' => $promotion->created_at,
                    'updated_at' => $promotion->updated_at
                ];

                // Gán gift chỉ khi là chương trình mua tặng
                if ($promotion->promotion_type === 'buy_get') {
                    if (!empty($promotion->giftProduct)) {
                        $data['gift'] = [
                            'type' => 'all product',
                            'product_id' => $promotion->giftProduct->id
                        ];
                    } elseif (!empty($promotion->giftProductVariant)) {
                        $variant = $promotion->giftProductVariant;
                        $data['gift'] = [
                            'type' => 'variant',
                            'parent_id' => $variant->product_id,
                            'variant_id' => $variant->id
                        ];
                    }

                    $data['buy_quantity'] = $promotion->buy_quantity;
                    $data['get_quantity'] = $promotion->get_quantity;
                }

                // Gán discount info nếu là giảm giá
                if (in_array($promotion->promotion_type, ['percentage', 'fixed_amount'])) {
                    $data['discount_type'] = $promotion->discount_type;
                    $data['discount_value'] = $promotion->discount_value;
                }

                // Danh sách sản phẩm áp dụng
                $applied = collect();

                if ($promotion->products->isNotEmpty()) {
                    foreach ($promotion->products as $product) {
                        $applied->push([
                            'type' => 'all product',
                            'product_id' => $product->id
                        ]);
                    }
                } else {
                    foreach ($promotion->productVariants as $variant) {
                        $applied->push([
                            'type' => 'variant',
                            'parent_id' => $variant->product_id,
                            'variant_id' => $variant->id
                        ]);
                    }
                }

                $data['applied_products'] = $applied;

                return $data;
            });

            return $listPromotions;
        } catch (Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Something went wrong!!!');
        }
    }


    public function show(string $id)
    {
        try {
            $promotion = Promotion::with(['products', 'productVariants', 'giftProduct', 'giftProductVariant'])
                                  ->find( $id);

            // Nếu không có promotion trả về lỗi
            if (empty($promotion)) {
                throw new ApiException('Không tìm thấy chương trình khuyến mãi này!!', 404);
            }

            $data = [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'promotion_type' => $promotion->promotion_type,
                'start_date' => $promotion->start_date,
                'end_date' => $promotion->end_date,
                'status' => $promotion->status ? 'active' : 'inactive',
                'created_at' => $promotion->created_at,
                'updated_at' => $promotion->updated_at
            ];

            // Gán gift nếu là mua tặng
            if ($promotion->promotion_type === 'buy_get') {
                if (!empty($promotion->giftProduct)) {
                    $data['gift'] = [
                        'type' => 'all product',
                        'product_id' => $promotion->giftProduct->id
                    ];
                } else {
                    $variant = $promotion->giftProductVariant;
                    $data['gift'] = [
                        'type' => 'variant',
                        'parent_id' => $variant->product_id,
                        'variant_id' => $variant->id
                    ];
                }

                $data['buy_quantity'] = $promotion->buy_quantity;
                $data['get_quantity'] = $promotion->get_quantity;
            }

            // Nếu là dạng giảm giá
            if (in_array($promotion->promotion_type, ['percentage', 'fixed_amount'])) {
                $data['discount_type'] = $promotion->discount_type;
                $data['discount_value'] = $promotion->discount_value;
            }

            // Danh sách sản phẩm áp dụng
            $applied = collect();

            if ($promotion->products->isNotEmpty()) {
                foreach ($promotion->products as $product) {
                    $applied->push([
                        'type' => 'all product',
                        'product_id' => $product->id
                    ]);
                }
            } else {
                foreach ($promotion->productVariants as $variant) {
                    $applied->push([
                        'type' => 'variant',
                        'parent_id' => $variant->product_id,
                        'variant_id' => $variant->id
                    ]);
                }
            }

            $data['applied_products'] = $applied;

            return $data;

        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            logger('Log bug promotion show', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Something went wrong!!!');
        }
    }

}
