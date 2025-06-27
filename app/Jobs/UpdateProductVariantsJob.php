<?php

namespace App\Jobs;

use App\Models\Product;
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
            $product = Product::with(['variants.images'])
                              ->find($this->productId);

            // update variant
            foreach($this->variants as $data) {
                $model = $product->variants->firstWhere('id','=',$data['id']);

                if($model) {
                    $model->updateOrInsert([
                        'sku' => $data['sku'],
                        'price' => $data['price'],
                        'sale_price' => $data['sale_price'],
                        'quantity' => $data['quantity'],
                        'is_active' => $data['is_active'],
                    ]);



                    $images = $data['image'];

                    // tạo lại ảnh cho variant
                    if (is_array($images)) {
                        foreach ($images as $img) {
                            $imageRecord = $model->images->firstWhere('id', $img['id']);
                            if ($imageRecord) {
                                $imageRecord->updateOrInsert([
                                    'image_url' => $img['url']
                                ]);
                            }
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
