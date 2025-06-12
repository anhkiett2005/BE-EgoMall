<?php
namespace App\Services;

use App\Classes\Common;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\VariantOption;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductServices {

    public function modifyIndex()
    {
        $products = Product::with(['category','brand','images','variants'])
                            ->get();

        $result = [];

        foreach($products as $product) {
            if(!$product->is_variable) {
                $result[] = [
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'category' => $product->category->name,
                    'brand' => $product->brand->name,
                    'images' => $product->images->map(function($image) {
                        return [
                            'url' => $image->image_url
                        ];
                    })->values(),
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'quantity' => $product->quantity,
                    'stock_status' => $product->stock_status,
                    'is_active' => $product->is_active,
                    'description' => $product->description,
                    'type_skin' => $product->type_skin,
                    'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
                ];
            }else {
                // Néu là variant thì lấy product_variant
                foreach($product->variants as $variant) {
                    $result[] = [
                        'name' => $variant->name,
                        'slug' => $variant->slug,
                        'sku' => $variant->sku,
                        'category' => $variant->product->category->name,
                        'brand' => $variant->product->brand->name,
                        'images' => $variant->product->images->map(function($image) {
                            return [
                                'url' => $image->image_url
                            ];
                        })->values(),
                        'price' => $variant->price,
                        'sale_price' => $variant->sale_price,
                        'quantity' => $variant->quantity,
                        'stock_status' => $variant->stock_status,
                        'is_active' => $variant->product->is_active,
                        'description' => $variant->product->description,
                        'type_skin' => $variant->product->type_skin,
                        'created_at' => $variant->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $variant->updated_at->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        return $result;
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->all();
        DB::beginTransaction();

        try {
            // Tạo sản phẩm cha mặc định
            $product = Product::create([
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name']) ?? null,
                    'category_id' => $data['category_id'],
                    'sku' => $data['sku'] ?? null,
                    'price' => $data['price'] ?? null,
                    'sale_price' => $data['sale_price'] ?? null,
                    'quantity' => $data['quantity'] ?? null,
                    'stock_status' => (!$data['is_variable'] && empty($data['variants'])) ? 'in_stock' : null,
                    'is_variable' => $data['is_variable'] ?? false,
                    'is_active' => $data['is_active'],
                    'brand_id' => $data['brand_id'] ?? null,
                    'type_skin' => $data['type_skin'] ?? null,
                    'description' => $data['description'] ?? null,
                    'image' => $data['image'] ?? null
            ]);

            // Nếu có biến thể
            if($data['is_variable'] && !empty($data['variants'])) {
                foreach($data['variants'] as $variant) {
                    // Tạo name cho variant
                    $variantName = Common::generateVariantName($product->name, $variant['options']);

                    $productVariant = $product->variants()->create([
                        'name' => $variantName,
                        'sku' => $variant['sku'],
                        'slug' => Str::slug($variantName),
                        'price' => $variant['price'],
                        'sale_price' => $variant['sale_price'] ?? null,
                        'quantity' => $variant['quantity'],
                        'stock_status' => 'in_stock',
                    ]);

                    // check các options của variant
                    foreach($variant['options'] as $optionName => $optionValue) {
                        // tìm hoặc tạo mới options
                        $variantOption = VariantOption::firstOrCreate([
                            'name' => $optionName
                        ]);

                        // tạo variant_value nếu ch có
                        $variantValue = $variantOption->variantValues()->firstOrCreate([
                            'value' => $optionValue
                        ]);

                        // ghi nhan variant_value cho product_variant
                        $productVariant->values()->create([
                            'variant_value_id' => $variantValue->id
                        ]);
                    }
                }
            }

            // Thêm hình ảnh
            foreach($data['images'] as $image) {
                $product->images()->create([
                    'image_url' => $image,
                ]);
            }

            DB::commit();

            return $product;
        } catch (Exception $e) {
            DB::rollBack();
            logger('Log bug',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

    }

    public static function showProduct(string $slug): ?array
    {
        // Tìm theo slug của sản phẩm chính
        $product = Product::with(['category', 'brand', 'images'])
                    ->where('slug', $slug)
                    ->first();

        if ($product) {
            return ['product' => [
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'category' => $product->category->name,
                'brand' => $product->brand->name,
                'images' => $product->images->map(fn($image) => [
                    'url' => $image->image_url
                ]),
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'quantity' => $product->quantity,
                'stock_status' => $product->stock_status,
                'description' => $product->description,
                'type_skin' => $product->type_skin,
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
            ]];
        }

        // Nếu là biến thể
        $variantProduct = Product::with([
                                'category',
                                'brand',
                                'images',
                                'variants' => fn($query) => $query->where('slug', $slug)
                            ])
                        ->whereHas('variants', fn($query) => $query->where('slug', $slug))
                        ->first();

        if ($variantProduct && $variantProduct->variants->isNotEmpty()) {
            $variant = $variantProduct->variants->first();
            return ['product' => [
                'name' => $variant->name ?? $variantProduct->name,
                'slug' => $variant->slug,
                'sku' => $variant->sku,
                'category' => $variantProduct->category->name,
                'brand' => $variantProduct->brand->name,
                'images' => $variantProduct->images->map(fn($image) => [
                    'url' => $image->image_url
                ]),
                'price' => $variant->price,
                'sale_price' => $variant->sale_price,
                'quantity' => $variant->quantity,
                'type_skin' => $variantProduct->type_skin,
                'stock_status' => $variant->stock_status,
                'created_at' => $variant->created_at->format('Y-m-d H:i:s'),
            ]];
        }

        return null;
    }

}
