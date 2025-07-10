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
                        'category' => $product->category->id ?? null,
                        'brand' => $product->brand->id ?? null,
                        'type_skin' => $product->type_skin ?? null,
                        'description' => $product->description ?? null,
                        'image' => $product->image ?? null,
                        'average_rating' => $averageRating,
                        'review_count' => $reviewCount,
                        'variants' => $product->variants->map(function ($variant) {
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
            }),
        ];
    }
}
