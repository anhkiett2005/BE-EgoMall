<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Promotion;
use App\Response\ApiResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * API trả về FE trang chủ
     *
     */
    public function index()
    {
        // lấy product and các vairant và đánh giá trung bình review về sản phẩm này and filter
        $request = request();
        $sub = DB::table('product_variants')
                 ->selectRaw('MAX(COALESCE(sale_price, price))')
                 ->whereColumn('product_variants.product_id', 'products.id');

        $query = Product::query()
                ->select('*')
                ->selectSub($sub, 'max_price')
                ->with([
                    'category',
                    'brand',
                    'variants' => function($query) {
                        $query->where('is_active', '!=', 0)
                            ->with([
                                'images',
                                'values.variantValue.option',
                                'orderDetails.order.review'
                            ]);
                    }
                ])
                ->where('is_active', '!=', 0);

        // Lọc theo category
        if($request->has('category')) {
            $query->where('category_id', '=', $request->category);
        }

        // Lọc theo brand (nếu có từ fe)
        if($request->has('brand')) {
            $query->where('brand_id', '=', $request->brand);
        }

        // Lọc theo loại da
        if($request->has('type_skin')) {
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
        if($request->filled('price_range')) {
            //Xử lý tách giá min và max
            [$min, $max] = array_map('intval',explode('-', $request->price_range));
            $query->whereHas('variants', function ($q) use($min, $max) {
               $q->where(function($subQ) use($min, $max) {
                   $subQ->where(function($hasSalePriceQ) use($min, $max) {
                        // nếu có sale_price  thì lọc theo giá sale vì variant áp dụng theo giá sale
                        $hasSalePriceQ->whereNotNull('sale_price')
                                      ->whereBetween('sale_price', [$min, $max]);
                   })
                   ->orWhere(function($noSalePriceQ) use($min, $max) {
                        // nếu sale_price null thì lọc theo price
                        $noSalePriceQ->whereNull('sale_price')
                                     ->whereBetween('price', [$min, $max]);
                   });
               });
            });
        }

        // Sort theo các tiêu chí
        if($request->has('sort')) {
            $this->sortProduct($request, $query);
        }



        // xử lý dữ liệu và trả về
        $products = $query->get();



        $productLists = $products->map(function($product): array {
            $allReviews = collect();

            foreach ($product->variants as $variant) {
                foreach ($variant->orderDetails as $detail) {
                    if ($detail->order && $detail->order->review) {
                        $allReviews->push($detail->order->review);
                    }
                }
            }

            $averageRating = $allReviews->avg('rating') ?? 0;
            $reviewCount = $allReviews->count();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => $product->category->id,
                'brand' => $product->brand->id ?? null,
                'type_skin' => $product->type_skin ?? null,
                'description' => $product->description ?? null,
                'image' => $product->image ?? null,
                'average_rating' => $averageRating,
                'review_count' => $reviewCount,
                'variants' => $product->variants->map(function($variant) {
                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'price' => $variant->price,
                        'sale_price' => $variant->sale_price,
                        'options' => $variant->values->map(function ($value) {
                            return [
                                'name' => $value->variantValue->option->name,
                                'value' => $value->variantValue->value
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return ApiResponse::success('Lấy danh sách sản phẩm thành công!!', data: $productLists);
    }



    public function show($slug)
    {
        $product = Product::with([
            'brand',
            'variants' => function ($query) {
                $query->where('is_active', '!=', 0)
                    ->with([
                        'images',
                        'values.variantValue.option',
                        'orderDetails.order.user' => function ($q) {
                            $q->where('is_active', '!=', 0)
                              ->select('id','name','image');
                        },
                        'orderDetails.order.review' => function ($q) {
                            $q->where('review_status', 'approved');
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

        $listDetails = collect();

        // Tính rating trung bình và số lượng đánh giá và lấy ra đánh giá của sản phẩm đó
        $allReviews = collect();

        foreach ($product->variants as $variant) {
            foreach ($variant->orderDetails as $detail) {
                if ($detail->order->review && $detail->order->user !== null) {
                    $allReviews->push($detail->order->review);
                }
            }
        }

        $averageRating = $allReviews->avg('rating') ?? 0;
        $reviewCount = $allReviews->count();
        $reviews = $allReviews->map(function ($review) {
            return [
                'name' => $review->order->user->name,
                'image' => $review->order->user->image,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'date' => Carbon::parse($review->created_at)->format('d-m-Y H:i'),
            ];
        });

        // trả về các list variants của sản phẩm
        $variantLists = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'sale_price' => $variant->sale_price,
                'image' => $variant->images->first()->image_url ?? null,
                'quantity' => $variant->quantity,
                'options' => $variant->values->map(function ($value) {
                    return [
                        'name' => $value->variantValue->option->name,
                        'value' => $value->variantValue->value
                    ];
                })->values(),
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
                                            'orderDetails.order.review' => function($q) {
                                                $q->where('review_status', 'approved');
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
                    if ($detail->order && $detail->order->review) {
                        $allReviewRelateds->push($detail->order->review);
                    }
                }
            }
        }

        $averageRatingRelated = $allReviewRelateds->avg('rating') ?? 0;
        $reviewCountRelated = $allReviewRelateds->count();

        // trả về sản phẩm cùng loại
        $relatedProducts->each(function ($relatedProduct) use (&$related, $averageRatingRelated, $reviewCountRelated) {
            $related->push([
                'id' => $relatedProduct->id,
                'name' => $relatedProduct->name,
                'slug' => $relatedProduct->slug,
                'price' => $relatedProduct->variants->avg('price'),
                'sale_price' => $relatedProduct->variants->avg('sale_price'),
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
            'variants' => $variantLists,
            'related' => $related
        ]);

        return ApiResponse::success('Data fetched successfully', data: $listDetails);
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

}
