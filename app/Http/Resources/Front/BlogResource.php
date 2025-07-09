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
                    return [
                        'id'   => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'image_url' => $product->image_url,
                        'category' => $product->category ? [
                            'name' => $product->category->name,
                        ] : null,
                        'brand' => $product->brand ? [
                            'name' => $product->brand->name,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
