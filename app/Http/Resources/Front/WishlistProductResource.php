<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Resources\Json\JsonResource;

class WishlistProductResource extends JsonResource
{
    public function toArray($request)
    {
        $allReviews = collect();

        foreach ($this->variants as $variant) {
            foreach ($variant->orderDetails as $detail) {
                if ($detail->order && $detail->order->review) {
                    $allReviews->push($detail->order->review);
                }
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category?->id,
            'brand' => $this->brand?->id,
            'type_skin' => $this->type_skin,
            'description' => $this->description,
            'image' => $this->image,
            'average_rating' => round($allReviews->avg('rating') ?? 0, 1),
            'review_count' => $allReviews->count(),
            'variants' => $this->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'sale_price' => $variant->sale_price,
                    'options' => $variant->values->map(function ($value) {
                        return [
                            'name' => $value->variantValue->option->name,
                            'value' => $value->variantValue->value,
                        ];
                    }),
                ];
            }),
        ];
    }
}
