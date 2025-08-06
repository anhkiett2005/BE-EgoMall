<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Models\Promotion;
use App\Models\PromotionProduct;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PromotionServices
{

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
                }

                if ($promotion->productVariants->isNotEmpty()) {
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

    /**
     * Lấy một promotion
     */
    public function show(string $id)
    {
        try {
            $promotion = Promotion::with(['products', 'productVariants', 'giftProduct', 'giftProductVariant'])
                ->find($id);

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
            }

            if ($promotion->productVariants->isNotEmpty()) {
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

    /**
     *  Tạo mới một promotion
     */

    public function store($request)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();

            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);

            // Lấy toàn bộ product_id và variant_id (lọc null và không trùng)
            $productIds = collect($data['applicable_products'])->pluck('product_id')->filter()->unique();
            $variantIds = collect($data['applicable_products'])->pluck('variant_id')->filter()->unique();

            // Check trùng khuyến mãi
            $isExist = DB::table('promotion_product as pp')
                ->join('promotions as p', 'p.id', '=', 'pp.promotion_id')
                ->whereIn('p.status', [0, 1])
                ->where(function ($q1) use ($start, $end) {
                    // Chỉ cần có giao là từ chối (bao gồm trùng start hoặc end)
                    $q1->where('p.start_date', '<=', $end)
                        ->where('p.end_date', '>=', $start);
                })
                ->exists();

            if ($isExist) {
                throw new ApiException('Đã có chương trình khuyến mãi áp dụng trong thời gian đã chọn!!', Response::HTTP_CONFLICT);
            }

            // Xử lý các field theo loại promotion
            if ($data['promotion_type'] === 'buy_get') {
                $data['discount_type'] = null;
                $data['discount_value'] = null;
            } else {
                $data['buy_quantity'] = null;
                $data['get_quantity'] = null;
                $data['gift_product_id'] = null;
                $data['gift_product_variant_id'] = null;
            }

            // Kiểm tra xem có chương trình đang active không
            // $hasActivePromotion = Promotion::where('status', '=', 1)
            //     ->where(function ($q) use ($start, $end) {
            //         $q->whereBetween('start_date', [$start, $end])
            //           ->orWhereBetween('end_date', [$start, $end])
            //           ->orWhere(function ($q1) use ($start, $end) {
            //                 $q1->where('start_date', '<', $start)
            //                     ->where('end_date', '>', $end);
            //           });
            //     })
            //     ->exists();

            $hasActivePromotion = Promotion::where('status', '=', 1)->exists();

            // Tạo promotion
            $promotion = Promotion::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'promotion_type' => $data['promotion_type'],
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $hasActivePromotion ? 0 : 1,
                'buy_quantity' => $data['buy_quantity'],
                'get_quantity' => $data['get_quantity'],
                'gift_product_id' => $data['gift_product_id'] ?? null,
                'gift_product_variant_id' => $data['gift_product_variant_id'] ?? null,
            ]);

            // Gắn các sản phẩm áp dụng vào promotion
            foreach ($data['applicable_products'] as $item) {
                PromotionProduct::create([
                    'promotion_id' => $promotion->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_variant_id' => $item['variant_id'] ?? null,
                ]);
            }

            DB::commit();

            // Nếu promotion active → gửi mail cho tất cả user đã xác thực
            if ($promotion->status === 1) {
                try {
                    Common::sendPromotionEmails($promotion);
                } catch (\Throwable $e) {
                    logger()->error('Gửi mail thất bại sau khi tạo promotion', [
                        'promotion_id' => $promotion->id,
                        'error_message' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString(),
                    ]);
                }
            }


            return $promotion; // Trả về model instance (có thể query tiếp)
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!!');
        }
    }

    /**
     *  Cập nhật một promotion
     */

    public function update($request, string $id)
    {
        DB::beginTransaction();

        try {
            // Lấy data từ request
            $data = $request->all();

            $data = Common::normalizePromotionFields($data);

            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);

            $productIds = collect($data['applicable_products'])->pluck('product_id')->filter()->unique();
            $variantIds = collect($data['applicable_products'])->pluck('variant_id')->filter()->unique();

            // Tìm promotion
            $promotion = Promotion::with(['products', 'productVariants'])
                ->find($id);

            if (!$promotion) {
                throw new ApiException('Không tìm thấy chương trình khuyến mãi!!', Response::HTTP_NOT_FOUND);
            }

            // check nếu có 1 promotion đang diễn ra throw exception luôn k cho update nữa
            // if ($promotion->status !== 0) {
            //     // Lấy số ngày còn lại của chương trình đang hoat động
            //     $now = Carbon::now();
            //     $endDate = Carbon::parse($promotion->end_date);

            //     // Nếu còn thời gian tính cả phần giờ phút giây thì dùng diffInRealDays (Carbon 2+)
            //     $daysLeft = ceil($now->floatDiffInDays($endDate));


            //     throw new ApiException('Không thể cập nhật vì có chương trình đang diễn ra, thử lại sau ' . $daysLeft . ' ngày!!', Response::HTTP_CONFLICT);
            // }

            // Check trùng khuyến mãi
            $isExist = DB::table('promotion_product as pp')
                ->join('promotions as p', 'p.id', '=', 'pp.promotion_id')
                ->where('p.id', '!=', $id)
                ->whereIn('p.status', [0, 1])
                ->where(function ($q1) use ($start, $end) {
                    // Chỉ cần có giao là từ chối (bao gồm trùng start hoặc end)
                    $q1->where('p.start_date', '<=', $end)
                        ->where('p.end_date', '>=', $start);
                })
                ->exists();

            if ($isExist) {
                throw new ApiException('Đã có chương trình khuyến mãi áp dụng trong thời gian đã chọn!!', Response::HTTP_CONFLICT);
            }

            // Xử lý các field theo loại promotion
            if ($data['promotion_type'] === 'buy_get') {
                $data['discount_type'] = null;
                $data['discount_value'] = null;
            } else {
                $data['buy_quantity'] = null;
                $data['get_quantity'] = null;
                $data['gift_product_id'] = null;
                $data['gift_product_variant_id'] = null;
            }

            $hasActivePromotion = Promotion::where('status', '=', 1)->exists();

            if ($hasActivePromotion && $data['status'] == true) {
                throw new ApiException('Không thể cập nhật trạng thái hoạt động vì đang có chương trình đang diễn ra!!', Response::HTTP_CONFLICT);
            }

            // cập nhật promotion
            $promotion->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'promotion_type' => $data['promotion_type'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $data['status'] ?? true,
                'buy_quantity' => $data['buy_quantity'] ?? null,
                'get_quantity' => $data['get_quantity'] ?? null,
                'gift_product_id' => $data['gift_product_id'] ?? null,
                'gift_product_variant_id' => $data['gift_product_variant_id'] ?? null,
            ]);

            // Sync sản phẩm áp dụng
            Common::syncApplicableProducts($promotion, $data['applicable_products']);

            DB::commit();
            return $promotion;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!!');
        }
    }

    /**
     *  Xóa một promotion
     */

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            // Tìm promotion
            $promotion = Promotion::find($id);

            if (!$promotion) {
                throw new ApiException('Không tìm thấy chương trình khuyến mãi!!', Response::HTTP_NOT_FOUND);
            }

            // Xóa promotion
            $promotion->delete();

            DB::commit();
            return $promotion;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!!');
        }
    }
}
