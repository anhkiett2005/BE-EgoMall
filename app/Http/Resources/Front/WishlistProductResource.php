<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Resources\Json\JsonResource;

class WishlistProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category?->id,
            'brand' => $this->brand?->id,
            'type_skin' => $this->type_skin,
            'description' => $this->description,
            'image' => $this->image,
        ];
    }
}
