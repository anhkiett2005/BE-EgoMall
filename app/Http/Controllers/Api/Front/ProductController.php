<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Carbon;

class ProductController extends Controller
{
    /**
     * API trả về FE trang chủ
     *
     */
    public function index()
    {
        //  Lấy product khuyến mãi
        $promotion = Promotion::where('start_date','<=', Carbon::now())
                              ->where('end_date','>=', Carbon::now())
                              ->where('status','!=',0)
                              ->first();
        $promotionEndDate = null;
        $promotionRemainingTime = null;

        if ($promotion) {
        $promotionEndDate = $promotion->end_date;

        // Tính thời gian còn lại
        $end = Carbon::parse($promotionEndDate);
        $diffInHours = $end->toIso8601String();

        if ($diffInHours) {
            $promotionRemainingTime = $diffInHours;
        }


            $productPromotion = collect();

            if ($promotion) {
                if ($promotion->products()->exists()) {
                    // Lấy theo sản phẩm cha nếu có
                    $productPromotion = $promotion->products()
                        ->with(['category', 'brand', 'variants.images'])
                        ->take(10)
                        ->get()
                        ->flatMap(function ($product) {
                            // Nếu sản phẩm có biến thể thì lấy toàn bộ biến thể
                            return $product->variants->map(function ($variant) use ($product) {
                                return [
                                    'name' => $product->name,
                                    'slug' => $product->slug,
                                    'sku' => $variant->sku,
                                    'price' => $variant->price,
                                    'sale_price' => $variant->sale_price,
                                    'brand_name' => optional($product->brand)->name,
                                    'images' => $variant->images->take(2)->map(function ($image) {
                                        return [
                                            'image_url' => $image->image_url
                                        ];
                                    })->values()
                                ];
                            });
                        });
                } else {
                    // Nếu không có product cha, lấy trực tiếp các biến thể trong chương trình
                    $productPromotion = $promotion->productVariants()
                        ->with(['product.brand','product.category', 'images']) // eager load product cha để lấy name, slug, brand
                        ->take(10)
                        ->get()
                        ->map(function ($variant) {
                            $product = $variant->product;

                            return [
                                'name' => optional($product)->name,
                                'slug' => optional($product)->slug,
                                'sku' => $variant->sku,
                                'category' => optional(optional($product)->category)->name,
                                'price' => $variant->price,
                                'sale_price' => $variant->sale_price,
                                'brand_name' => optional(optional($product)->brand)->name,
                                'images' => $variant->images->take(2)->map(function ($image) {
                                    return [
                                        'image_url' => $image->image_url
                                    ];
                                })->values()
                            ];
                        });
                }
            }

        // Lấy product có danh mục bán chạy
        $productBestSelling = Product::with(['category', 'brand','images'])
        ->whereHas('category', function($q) {
            $q->where('slug', 'like', '%ban-chay%')
              ->select('slug');
        })
        ->paginate(8)
        ->transform(function($product) {
            if($product->is_variable == true) {
                $variant = $product->variants->first();
                // lấy ra variant đầu tiên
                if($variant) {
                    $product->sku = $variant->sku;
                    $product->price = $variant->price;
                    $product->sale_price = $variant->sale_price;
                }
            }

            // Lấy brand name thay vì object brand
            $product->brand_name = optional($product->brand)->name;

            return [
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'brand_name' => $product->brand_name,
                'images' => $product->images->take(2)->map(function($image) {
                     return [
                        'image_url' => $image->image_url
                    ];
                })->values()
            ];
        });
        }

        // Lấy sản phẩm khuyến mãi ngẫu nhiên


        // lấy product có danh mục sửa rữa mặt
        $productFaceWash = Product::with(['category', 'brand','images'])
        ->whereHas('category', function($q) {
            $q->where('slug', 'like', '%sua-rua-mat%')
              ->select('slug');
        })
        ->paginate(8)
        ->transform(function($product) {
            if($product->is_variable == true) {
                $variant = $product->variants->first();
                // lấy ra variant đầu tiên
                if($variant) {
                    $product->sku = $variant->sku;
                    $product->price = $variant->price;
                    $product->sale_price = $variant->sale_price;
                }
            }

            // Lấy brand name thay vì object brand
            $product->brand_name = optional($product->brand)->name;

            return [
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'brand_name' => $product->brand_name,
                'images' => $product->images->take(2)->map(function($image) {
                     return [
                        'image_url' => $image->image_url
                    ];
                })->values()
            ];
        });

        // lấy product có danh mục xả kho
        $productClearanceSale = Product::with(['category', 'brand','images'])
        ->whereHas('category', function($q) {
            $q->where('slug', 'like', '%xa-kho%')
              ->select('slug');
        })
        ->paginate(5)
        ->transform(function($product) {
            if($product->is_variable == true) {
                $variant = $product->variants->first();
                // lấy ra variant đầu tiên
                if($variant) {
                    $product->sku = $variant->sku;
                    $product->price = $variant->price;
                    $product->sale_price = $variant->sale_price;
                }
            }

            // Lấy brand name thay vì object brand
            $product->brand_name = optional($product->brand)->name;

            return [
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'brand_name' => $product->brand_name,
                'images' => $product->images->take(2)->map(function($image) {
                     return [
                        'image_url' => $image->image_url
                    ];
                })->values()
            ];
        });

        return response()->json([
            'message' => 'success',
            'data' => [
                'promotion' => [
                    'promotionName' => $promotion->name,
                    'productPromotions' => $productPromotion,
                    'remainingTime' => $promotionRemainingTime
                ],
                'productBestSelling' => $productBestSelling,
                'productFaceWash' => $productFaceWash,
                'productClearanceSale' => $productClearanceSale
            ]
        ]);
    }
}
