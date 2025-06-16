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


    public function show($slug)
    {
        $product = Product::with([
                                'brand',
                                'variants' => function ($query) {
                                    $query->where('is_active', '!=', 0)
                                        ->with([
                                            'images',
                                            'values.variantValue.option'
                                        ]);
                                }
                            ])
                          ->where('slug', 'like', '%' . $slug . '%')
                          ->first();

        $listDetails = collect();

        // trả về các list variants của sản phẩm
        $variantLists = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'sale_price' => $variant->sale_price,
                'image' => $variant->images->first()->image_url,
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

        // trả về sản phẩm chi tiết
        $listDetails->push([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand->name,
            'image' => $product->image,
            'status' => $status,
            'variants' => $variantLists,
        ]);

        return ApiResponse::success('Data fetched successfully',data: $listDetails);

    }
}
