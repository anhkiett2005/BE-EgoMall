<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Promotion;
use App\Response\ApiResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    private function getLeafCategoryIds($category): array
    {
        $ids = [];

        foreach ($category->children as $child) {
            if ($child->children->isEmpty()) {
                $ids[] = $child->id;
            } else {
                $ids = array_merge($ids, $this->getLeafCategoryIds($child));
            }
        }

        return $ids;
    }

    /**
     * API trả về FE trang chủ
     *
     */
    public function index()
    {
        try {
            // lấy product and các vairant và đánh giá trung bình review về sản phẩm này and filter
            $request = request();
            $sub = DB::table('product_variants')
                ->selectRaw('MAX(COALESCE(sale_price, price))')
                ->whereColumn('product_variants.product_id', 'products.id');
            $now = now();

            $query = Product::query()
                ->select('*')
                ->selectSub($sub, 'max_price')
                ->with([
                    'category',
                    'brand',
                    'variants' => function ($query) {
                        $query->where('is_active', '!=', 0)
                            ->with([
                                'images',
                                'values',
                                'orderDetails.review'
                            ]);
                    }
                ])
                ->where('is_active', '!=', 0);

            // Lọc theo category
            // if ($request->has('category')) {
            //     $query->where('category_id', '=', $request->category);
            // }
            if ($request->has('category')) {
                $categorySlug = $request->category;

                $category = Category::with('children.children')
                    ->where('slug', $categorySlug)
                    ->first();

                if ($category) {
                    if ($category->children->isEmpty()) {
                        $query->where('category_id', $category->id);
                    } else {
                        $leafIds = $this->getLeafCategoryIds($category);
                        if (!empty($leafIds)) {
                            $query->whereIn('category_id', $leafIds);
                        } else {
                            $query->whereRaw('1=0'); // fallback
                        }
                    }
                }
            }

            // Lọc theo brand (nếu có từ fe)
            if ($request->has('brand')) {
                $brandSlug = $request->brand;

                $brand = Brand::where('slug', $brandSlug)->first();

                if ($brand) {
                    $query->where('brand_id', $brand->id);
                } else {
                    // fallback nếu không tìm thấy brand
                    $query->whereRaw('1=0');
                }
            }

            // Lọc sản phẩm đang nằm trong khuyến mãi
            if ($request->filled('has_promotion') && $request->has_promotion == 1) {
                $now = now();

                $query->where(function ($q) use ($now) {
                    $q->whereHas('promotions', function ($q1) use ($now) {
                        $q1->where('status', 1)
                            ->where('start_date', '<=', $now)
                            ->where('end_date', '>=', $now);
                    })->orWhereHas('variants.promotions', function ($q2) use ($now) {
                        $q2->where('status', 1)
                            ->where('start_date', '<=', $now)
                            ->where('end_date', '>=', $now);
                    });
                });
            }


            // Lọc theo loại da
            if ($request->has('type_skin')) {
                $query->where('type_skin', '=', $request->type_skin);
            }

            // Lọc theo loại sản phẩm
            if ($request->filled('type')) {
                $type = $request->type;

                $query->where(function ($q) use ($type) {
                    // Sản phẩm có tên giống từ khoá
                    $q->where('name', 'like', '%' . $type . '%')

                        // Hoặc thuộc danh mục (cha hoặc con) có tên giống từ khoá
                        ->orWhereHas('category', function ($catQ) use ($type) {
                            $catQ->where('name', 'like', '%' . $type . '%');
                        });
                });
            }

            // lọc theo mức giá từ min-> max
            if ($request->filled('price_range')) {
                //Xử lý tách giá min và max
                [$min, $max] = array_map('intval', explode('-', $request->price_range));
                // $query->whereHas('variants', function ($q) use($min, $max) {
                // $q->where(function($subQ) use($min, $max) {
                //     $subQ->where(function($hasSalePriceQ) use($min, $max) {
                //             // nếu có sale_price  thì lọc theo giá sale vì variant áp dụng theo giá sale
                //             $hasSalePriceQ->whereNotNull('sale_price')
                //                         ->whereBetween('sale_price', [$min, $max]);
                //     })
                //     ->orWhere(function($noSalePriceQ) use($min, $max) {
                //             // nếu sale_price null thì lọc theo price
                //             $noSalePriceQ->whereNull('sale_price')
                //                         ->whereBetween('price', [$min, $max]);
                //     });
                // });
                // });

                $query->whereHas('variants', function ($q) use ($min, $max) {
                    $q->whereRaw("
                        COALESCE(
                            sale_price,
                            price - (
                                COALESCE(
                                    (
                                        SELECT
                                            CASE
                                                WHEN p.promotion_type = 'percentage'
                                                    THEN price * (p.discount_value / 100)
                                                WHEN p.promotion_type = 'fixed_amount'
                                                    THEN p.discount_value
                                                ELSE 0
                                            END
                                        FROM promotions p
                                        JOIN promotion_product pp
                                            ON p.id = pp.promotion_id
                                        WHERE pp.product_variant_id = product_variants.id
                                            AND p.status = 'active'
                                            AND p.start_date <= ?
                                            AND p.end_date >= ?
                                        ORDER BY p.discount_value DESC
                                        LIMIT 1
                                    ), 0
                                )
                            )
                        ) BETWEEN ? AND ?
                    ", [now(), now(), $min, $max]);
                });
            }

            // Sort theo các tiêu chí
            if ($request->has('sort')) {
                $this->sortProduct($request, $query);
            }


            $promotions = self::getActivePromotions();

            // xử lý dữ liệu và trả về
            $products = $query->get();



            $productLists = $products->map(function ($product) use ($promotions): array {
                $allReviews = collect();

                foreach ($product->variants as $variant) {
                    foreach ($variant->orderDetails as $detail) {
                        if ($detail && $detail->review) {
                            $allReviews->push($detail->review);
                        }
                    }
                }

                $averageRating = $allReviews->avg('rating') ?? 0;
                $reviewCount = $allReviews->count();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'category' => $product->category->id ?? null,
                    'brand'    => $product->brand->id ?? null,
                    'type_skin' => $product->type_skin ?? null,
                    'description' => $product->description ?? null,
                    'image' => $product->image ?? null,
                    'average_rating' => $averageRating,
                    'review_count' => $reviewCount,
                    'options' => $product->variants
                        ->flatMap(function ($variant) {
                            return $variant->values;
                        })
                        ->groupBy(fn($value) => $value->option->id ?? null)
                        ->filter(fn($group, $optionId) => $optionId !== null)
                        ->map(function ($group, $optionId) {
                            $option = $group->first()->option;

                            return [
                                'id' => $option->id,
                                'name' => $option->name,
                                'value_ids' => $group->map(function ($value) {
                                    return [
                                        $value->id,
                                    ];
                                })->unique()->flatten()->toArray(), // bỏ key
                                'value_labels' => $group->map(function ($value) {
                                    return [
                                        $value->value
                                    ];
                                })->unique()->flatten()->toArray(),
                            ];
                        })->values(), // bỏ key
                    'variants' => $product->variants->map(function ($variant) use ($promotions) {
                        return [
                            'id' => $variant->id,
                            'sku' => $variant->sku,
                            'price' => $variant->price,
                            'sale_price' => $variant->sale_price,
                            'final_price_discount' => self::checkPromotion($variant, $promotions),
                            'quantity' => $variant->quantity,
                            'is_active' => $variant->is_active,
                            'option_value_ids' => $variant->values->pluck('id')->toArray(),
                            'option_labels' => $variant->values->map(function ($label) {
                                return ($label->option->name ?? 'Thuộc tính') . ": " . $label->value;
                            })->implode(' | '),
                            'image' => $variant->images->pluck('image_url')->first(),
                            // 'options' => $variant->values->map(function ($value) {
                            //     return [
                            //         'name' => $value->option->name,
                            //         'value' => $value->value
                            //     ];
                            // })->values(),
                        ];
                    })->values(),
                ];
            });

            return ApiResponse::success('Lấy danh sách sản phẩm thành công!!', data: $productLists);
        } catch (\Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }



    public function show($slug)
    {
        try {
            $product = Product::with([
                'brand',
                'variants' => function ($query) {
                    $query->where('is_active', '!=', 0)
                        ->with([
                            'images',
                            'values',
                            'orderDetails.order.user' => function ($q) {
                                $q->where('is_active', '!=', 0)
                                    ->select('id', 'name', 'image');
                            },
                            'orderDetails.review' => function ($q) {
                                $q->where('status', 'approved');
                            }
                        ]);
                }
            ])
                ->where('slug', 'like', '%' . $slug . '%')
                ->where('is_active', '!=', 0)
                ->first();

            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }

            $promotions = self::getActivePromotions();

            $listDetails = collect();

            // Tính rating trung bình và số lượng đánh giá và lấy ra đánh giá của sản phẩm đó
            $allReviews = collect();

            foreach ($product->variants as $variant) {
                foreach ($variant->orderDetails as $detail) {
                    if ($detail->review && $detail->user !== null) {
                        $allReviews->push($detail->review);
                    }
                }
            }

            $averageRating = $allReviews->avg('rating') ?? 0;
            $reviewCount = $allReviews->count();
            $reviews = $allReviews->map(function ($review) {
                return [
                    'name' => $review->user->name,
                    'image' => $review->user->image,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'date' => Carbon::parse($review->created_at)->format('d-m-Y H:i'),
                ];
            });

            // Trả thêm các options của variants
            $options = $product->variants
                ->flatMap(function ($variant) {
                    return $variant->values;
                })
                ->groupBy(fn($value) => $value->option->id ?? null)
                ->filter(fn($group, $optionId) => $optionId !== null)
                ->map(function ($group, $optionId) {
                    $option = $group->first()->option;

                    return [
                        'id' => $option->id,
                        'name' => $option->name,
                        'value_ids' => $group->map(function ($value) {
                            return [
                                $value->id,
                            ];
                        })->unique()->flatten()->toArray(), // bỏ key
                        'value_labels' => $group->map(function ($value) {
                            return [
                                $value->value
                            ];
                        })->unique()->flatten()->toArray(),
                    ];
                })->values();


            // trả về các list variants của sản phẩm
            $variantLists = $product->variants->map(function ($variant) use ($promotions) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'sale_price' => $variant->sale_price,
                    'final_price_discount' => self::checkPromotion($variant, $promotions),
                    'quantity' => $variant->quantity,
                    'is_active' => $variant->is_active,
                    'option_value_ids' => $variant->values->pluck('id')->toArray(),
                    'option_labels' => $variant->values->map(function ($label) {
                        return ($label->option->name ?? 'Thuộc tính') . ": " . $label->value;
                    })->implode(' | '),
                    'image' => $variant->images->pluck('image_url')->first(),
                    // 'options' => $variant->values->map(function ($value) {
                    //     return [
                    //         'name' => $value->option->name,
                    //         'value' => $value->value
                    //     ];
                    // })->values(),
                ];
            });

            // Tính tổng số lượng của toàn bộ variant để check status
            $totalQuantity = $product->variants->sum('quantity');
            $status = $totalQuantity > 0 ? 'Còn hàng' : 'Hết hàng';

            // lấy sản phẩm cùng loại
            $related = collect();

            // Tính rating trung bình và số lượng đánh giá và lấy ra đánh giá của sản phẩm cùng loại
            $allReviewRelateds = collect();

            $relatedProducts = Product::with([
                'brand',
                'variants' => function ($query) {
                    $query->where('is_active', '!=', 0)
                        ->with([
                            'orderDetails.review' => function ($q) {
                                $q->where('status', 'approved');
                            }
                        ]);
                }
            ])
                ->where('category_id', '=', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_active', '!=', 0)
                ->inRandomOrder()
                ->limit(5)
                ->get();

            foreach ($relatedProducts as $relatedProduct) {
                foreach ($relatedProduct->variants as $variant) {
                    foreach ($variant->orderDetails as $detail) {
                        if ($detail && $detail->review) {
                            $allReviewRelateds->push($detail->review);
                        }
                    }
                }
            }

            $averageRatingRelated = $allReviewRelateds->avg('rating') ?? 0;
            $reviewCountRelated = $allReviewRelateds->count();

            // trả về sản phẩm cùng loại
            $relatedProducts->each(function ($relatedProduct) use (&$related, $averageRatingRelated, $reviewCountRelated, $promotions) {
                $related->push([
                    'id' => $relatedProduct->id,
                    'name' => $relatedProduct->name,
                    'slug' => $relatedProduct->slug,
                    'price' => $relatedProduct->variants->first()?->price,
                    'sale_price' => $relatedProduct->variants->first()?->sale_price,
                    'final_price_discount' => self::checkPromotion($relatedProduct->variants->first(), $promotions),
                    'brand' => $relatedProduct->brand->name ?? null,
                    'image' => $relatedProduct->image,
                    'average_rating' => $averageRatingRelated,
                    'review_count' => $reviewCountRelated,
                ]);
            });


            // trả về sản phẩm chi tiết
            $listDetails->push([
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => $product->brand->name ?? null,
                'image' => $product->image,
                'status' => $status,
                'average_rating' => $averageRating,
                'review_count' => $reviewCount,
                'reviews' => $reviews,
                'options' => $options,
                'variants' => $variantLists,
                'related' => $related
            ]);

            return ApiResponse::success('Data fetched successfully', data: $listDetails);
        } catch (\Exception $e) {
            logger('Log bug', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra!!');
        }
    }


    private function sortProduct($request, $query)
    {
        switch ($request->sort) {
            case 'a-z':
                $query->orderBy('name', 'asc');
                break;
            case 'z-a':
                $query->orderBy('name', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('max_price', 'asc'); // Đã có sẵn từ selectSub
                break;
            case 'price_desc':
                $query->orderBy('max_price', 'desc'); // Đã có sẵn từ selectSub
                break;
            case 'new':
                $query->where('created_at', '>=', now()->subDays(7)) // lọc hàng mới 7 ngày gần đây
                    ->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

    // Lấy danh sách promotion trong hệ thống đang hoat động
    private function getActivePromotions()
    {
        $now = now();
        return Promotion::with(['products', 'productVariants'])
            ->where('status', '!=', 0)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->get();
    }

    public static function checkPromotion($variant, $promotions)
    {
        if ($variant->sale_price !== null) return null;

        // Ưu tiên promotion theo variant
        $variantPromotion = $promotions->first(function ($promo) use ($variant) {
            return $promo->productVariants->contains('id', $variant->id);
        });

        if ($variantPromotion) {
            return self::calculateDiscount($variant, $variantPromotion);
        }

        // Nếu không, lấy promotion theo product
        $productPromotion = $promotions->first(function ($promo) use ($variant) {
            return $promo->products->contains('id', $variant->product_id);
        });

        if ($productPromotion) {
            return self::calculateDiscount($variant, $productPromotion);
        }

        return null;
    }

    // Hàm xử lý tính toán giảm giá
    protected static function calculateDiscount($variant, $promotion)
    {
        // dd($promotion);
        if ($variant->sale_price !== null) {
            return null;
        }

        $price = $variant->price;
        $discount = 0;

        if ($promotion->promotion_type === 'percentage') {
            $discount = $price * ($promotion->discount_value / 100);
        } elseif ($promotion->promotion_type === 'fixed_amount') {
            $discount = $promotion->discount_value;
        }

        return max(0, $price - $discount);
    }
}