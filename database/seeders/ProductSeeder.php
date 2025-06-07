<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\VariantOption;
use App\Models\VariantValue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo 5 sản phẩm KHÔNG có biến thể
        Product::factory()->count(5)->create([
            'is_variable' => false
        ])->each(function ($product) {
            $this->createProductImages($product);
            // Tạo variant mặc định (vì is_variable = false)
            ProductVariant::factory()->create([
                'product_id' => $product->id,
            ]);
        });

        // Tạo 5 sản phẩm CÓ biến thể
        Product::factory()->count(5)->create([
            'is_variable' => true
        ])->each(function ($product) {
            $this->createProductImages($product);

            // Tạo ngẫu nhiên 2 option cho mỗi sản phẩm
            $optionIds = VariantOption::get()->pluck('id')->toArray();

            // Lấy value tương ứng với mỗi option
            $valueSets = [];
            foreach ($optionIds as $optionId) {
                $values = VariantValue::where('option_id', $optionId)
                    ->inRandomOrder()
                    ->take(2)
                    ->pluck('id')
                    ->toArray();
                $valueSets[] = $values;
            }

            // Tạo tổ hợp các biến thể (Cartesian Product)
            $combinations = $this->cartesian($valueSets);

            foreach ($combinations as $valueSet) {
                $variant = ProductVariant::factory()->create([
                    'product_id' => $product->id,
                ]);

                // Gắn các value vào variant (giả sử có bảng trung gian `product_variant_values`)
                foreach ($valueSet as $valueId) {
                    DB::table('product_variant_values')->insert([
                        'product_variant_id' => $variant->id,
                        'variant_value_id' => $valueId,
                    ]);
                }
            }
        });
    }

    private function createProductImages($product)
    {
        $count = rand(1, 4);
        ProductImage::factory()->count($count)->create([
            'product_id' => $product->id,
        ]);
    }

    // Tạo tích Descartes từ các mảng (tổ hợp biến thể)
    private function cartesian($arrays)
    {
        $result = [[]];
        foreach ($arrays as $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property_value]);
                }
            }
            $result = $tmp;
        }
        return $result;
    }
}
