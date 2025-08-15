<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Resources\Json\JsonResource;

class WishlistProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,

            'category'   => [
                'id'   => $this->category->id   ?? null,
                'name' => $this->category->name ?? null,
            ],
            'brand'      => [
                'name' => $this->brand->name ?? null,
                'id'   => $this->brand->id   ?? null,
            ],

            'type_skin'  => $this->type_skin ?? null,
            'description'=> $this->description ?? null,
            'image'      => $this->image ?? null,
            'is_active'  => (bool) $this->is_active,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),

            // Giống option_selecteds bên product list
            'option_selecteds' => $this->variants
                ->flatMap(fn($variant) => $variant->values)
                ->groupBy(fn($value) => $value->option->id ?? null)
                ->filter(fn($group, $optionId) => $optionId !== null)
                ->map(function ($group) {
                    $option = $group->first()->option;
                    return [
                        'id'     => $option->id,
                        'name'   => $option->name,
                        'values' => $group->map(fn($value) => [
                            'id'    => $value->id,
                            'value' => $value->value,
                        ])->unique('id')->values()->toArray(),
                    ];
                })->values(),

            'variants' => $this->variants->map(function ($variant) {
                return [
                    'id'               => $variant->id,
                    'sku'              => $variant->sku,
                    'price'            => $variant->price,
                    'sale_price'       => $variant->sale_price,
                    'quantity'         => $variant->quantity,
                    'is_active'        => (bool) $variant->is_active,
                    'option_value_ids' => $variant->values->pluck('id')->toArray(),
                    'option_labels'    => $variant->values->map(function ($label) {
                        return ($label->option->name ?? 'Thuộc tính') . ': ' . $label->value;
                    })->implode(' | '),
                    'images' => $variant->images->map(fn($img) => [
                        'id'  => $img->id,
                        'url' => $img->image_url,
                    ])->values(),
                    'option_transform' => $variant->values->map(fn($value) => [
                        'name'  => $value->option->name,
                        'value' => $value->value,
                    ])->values(),
                ];
            })->values(),
        ];
    }
}