<?php
namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Http\Requests\StoreProductRequest;
use App\Jobs\UpdateProductVariantsJob;
use App\Models\CategoryOption;
use App\Models\Product;
use App\Models\VariantOption;
use App\Models\VariantValue;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductServices {

    /**
     * Lấy toàn bộ danh sách products
     */
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

    /**
     * Tạo mới một product
     */
    public function store($request)
    {
        $data = $request->all();
        DB::beginTransaction();

        try {
            // Tạo sản phẩm chứa thông tin chung
            $product = Product::create([
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name']) ?? null,
                    'category_id' => $data['category_id'],
                    'is_active' => $data['is_active'],
                    'brand_id' => $data['brand_id'] ?? null,
                    'type_skin' => $data['type_skin'] ?? null,
                    'description' => $data['description'] ?? null,
                    'image' => $data['image'] ?? null
            ]);

            // Nếu có biến thể
            if($data['is_variable'] && !empty($data['variants'])) {
                foreach($data['variants'] as $variant) {
                    // // Tạo name cho variant
                    // $variantName = Common::generateVariantName($product->name, $variant['options']);

                    $productVariant = $product->variants()->create([
                        'sku' => $variant['sku'],
                        'price' => $variant['price'],
                        'sale_price' => $variant['sale_price'] ?? null,
                        'quantity' => $variant['quantity'],
                        'is_active' => $variant['is_active']
                    ]);

                    foreach($variant['options'] as $optionId => $optionValue) {

                        // check nếu không nằm trong category_option thì báo lỗi
                        $isValidOption = CategoryOption::where('category_id','=',$product->category_id)
                                                       ->where('variant_option_id', '=', $optionId)
                                                       ->exists();

                        if (!$isValidOption) {
                            throw new ApiException("Option Id {$optionId} không hợp lệ với danh mục {$product->category->name}", 400);
                        }

                        // tạo variant_value từ các options gửi lên request, ch có thì tạo mới
                        $variantValue = VariantValue::firstOrCreate(
                            [
                                'option_id' => $optionId,
                                'value' => $optionValue
                            ]
                        );
                        // ghi nhan variant_value cho product_variant
                        $productVariant->values()->create([
                            'variant_value_id' => $variantValue->id
                        ]);
                    }

                    //  thêm hình ảnh cho variant
                    foreach($variant['images'] as $image) {
                        $productVariant->images()->create([
                            'image_url' => $image['url']
                        ]);
                    }
                }
            }

            DB::commit();

            return $product;
        } catch(ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            logger('Log bug',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Something went wrong!!!');
        }

    }

    /**
     * Show chi tiết một product
     */
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

    /**
     * Cập nhật một product
     */
    public function update($request, string $slug)
    {
        DB::beginTransaction();
        try {
            // Lấy data từ request
            $data = $request->all();

            // Tìm sản phẩm
            $product = Product::where('slug', '=', $slug)
                              ->first();

            if(!$product) {
                throw new ApiException('Không tìm thấy sản phẩm!!', 404);
            }


            // Update thông tin chung của product
            $product->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'is_active' => $data['is_active'],
                'brand_id' => $data['brand_id'],
                'type_skin' => $data['type_skin'] ?? null,
                'description' => $data['description'] ?? null,
                'image' => $data['image'] ?? null,
            ]);

            DB::commit();

            $variants = collect($data['variants'])->map(function ($variant) {
                $variant['image'] = is_array($variant['image'] ?? null) ? $variant['image'] : [];
                return $variant;
            })->toArray();

            // Đẩy variant vào queue để update
            dispatch(new UpdateProductVariantsJob($product->id, $variants));

            return $product;
        } catch(ApiException $e) {
            DB::rollBack();
            throw $e;
        }
        catch(Exception $e) {
            DB::rollBack();
            logger('Log bug update product',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Something went wrong!!!');
        }
    }

    /**
     * Xóa một product
     */
    public function destroy(string $slug)
    {
        DB::beginTransaction();

        try {
            // Tìm sản phẩm muốn xóa
            $product = Product::where('slug', '=', $slug)
                              ->first();

            // Nếu không tìm thấy trả về lỗi
            if(!$product) {
                throw new ApiException('Không tìm thấy sản phẩm!!', 404);
            }

            // Xóa product
            $product->delete();

            DB::commit();

            return $product;
        } catch (ApiException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            logger('Log bug delete product',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Something went wrong!!!');
        }
    }
}
