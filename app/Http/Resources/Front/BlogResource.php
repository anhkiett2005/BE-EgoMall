<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'content'       => $this->content,
            'excerpt'       => $this->excerpt,
            'image_url'     => $this->image_url,
            'status'        => $this->status,
            'views'         => $this->views,
            'published_at' => optional($this->published_at)->format('d F, Y'),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'name'  => $this->category->name,
                    'slug'  => $this->category->slug,
                ];
            }),

            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'name'  => $this->creator->name,
                ];
            }),

            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    // LẤY TỪ ATTRIBUTE ĐÃ GẮN TỪ SERVICE
                    $averageRating = (float) ($product->avg_rating   ?? 0.0);
                    $reviewCount   = (int)   ($product->review_count ?? 0);
                    $soldCount     = (int)   ($product->sold_count   ?? 0);

                    return [
                        'id'            => $product->id,
                        'name'          => $product->name,
                        'slug'          => $product->slug,
                        'category'      => $product->category->id ?? null,   // (có thể đổi sang name/slug như luồng product nếu cần)
                        'brand'         => $product->brand->id ?? null,       // idem
                        'type_skin'     => $product->type_skin ?? null,
                        'description'   => $product->description ?? null,
                        'image'         => $product->image ?? null,
                        'average_rating' => $averageRating,
                        'review_count'  => $reviewCount,
                        'sold_count'    => $soldCount,
                        'variants'      => $product->variants->map(function ($variant) {
                            return [
                                'id'         => $variant->id,
                                'sku'        => $variant->sku,
                                'price'      => $variant->price,
                                'sale_price' => $variant->sale_price,
                                'options'    => $variant->values->map(function ($value) {
                                    return [
                                        'name'  => $value->option->name,
                                        'value' => $value->value
                                    ];
                                })->values(),
                            ];
                        })->values(),
                    ];
                });
            }),

        ];
    }
}
