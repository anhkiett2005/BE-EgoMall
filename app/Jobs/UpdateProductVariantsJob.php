<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\VariantValue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateProductVariantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $variants;
    public $productId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $productId, array $variantData)
    {
        $this->productId = $productId;
        $this->variants = $variantData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::beginTransaction();

        try {
            // tìm variant để update
            $product = Product::with(['variants.values','variants.images'])
                              ->find($this->productId);

            // update variant
            foreach($this->variants as $data) {
            if (isset($data['id'])) {
                $model = $product->variants->firstWhere('id', $data['id']);

                if ($model) {
                    $model->update([
                        'sku' => $data['sku'],
                        'price' => $data['price'],
                        'sale_price' => $data['sale_price'],
                        'quantity' => $data['quantity'],
                        'is_active' => $data['is_active'],
                    ]);

                    $images = $data['image'] ?? [];

                    if (is_array($images)) {
                        foreach ($images as $img) {
                            $imageRecord = $model->images->firstWhere('id', $img['id']);

                            if ($imageRecord) {
                                $imageRecord->update([
                                    'image_url' => $img['url']
                                ]);
                            } else {
                                $model->images()->create([
                                    'image_url' => $img['url']
                                ]);
                            }
                        }
                    }
                }
                } else {
                    // Tạo mới variant nếu không có id
                    $model = $product->variants()->create([
                        'sku' => $data['sku'],
                        'price' => $data['price'],
                        'sale_price' => $data['sale_price'],
                        'quantity' => $data['quantity'],
                        'is_active' => $data['is_active'],
                    ]);

                    if (!empty($data['options']) && is_array($data['options'])) {
                        foreach ($data['options'] as $optionId => $value) {
                            $variantValue = VariantValue::firstOrCreate([
                                'option_id' => $optionId,
                                'value' => $value,
                            ]);

                            $model->values()->create([
                                'variant_value_id' => $variantValue->id,
                            ]);
                        }
                    }

                    $images = $data['image'] ?? [];

                    if (is_array($images)) {
                        foreach ($images as $img) {
                            $model->images()->create([
                                'image_url' => $img['url']
                            ]);
                        }
                    }
                }
        }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            // ghi log lại lỗi
            logger('Log bug update variant',[
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
