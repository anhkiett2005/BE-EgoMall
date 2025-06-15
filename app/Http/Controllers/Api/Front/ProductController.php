<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Promotion;
use App\Response\ApiResponse;
use Illuminate\Support\Carbon;

class ProductController extends Controller
{
    /**
     * API trả về FE trang chủ
     *
     */
    public function index()
    {
        //  Lấy product and các variant
        $products = Product::with(['category','brand','variants','variants.values.variantValue.option'])
                           ->get();

        $productLists = $products->map(function($product): array {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => optional($product->category)->id,
                'brand' => optional($product->brand)->id,
                'type_skin' => $product->type_skin ?? null,
                'description' => $product->description ?? null,
                'image' => $product->image ?? null,
                'variants' => $product->variants->map(function($variant) {
                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'price' => $variant->price,
                        'sale_price' => $variant->sale_price,
                        'options' => $variant->values->map(function ($value) {
                            return [
                                'name' => optional(optional($value->variantValue)->option)->name,
                                'value' => optional($value->variantValue)->value
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return ApiResponse::success('Data fetched successfully', data: $productLists);
    }
}
