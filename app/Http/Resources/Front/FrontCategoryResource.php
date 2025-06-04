<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FrontCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // base fields return to frontend
        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'thumbnail' => $this->thumbnail,
        ];

        if ($this->relationLoaded('brand') && $this->brand) {
            $data['brand_name'] = $this->brand->name;
            $data['brand_slug'] = $this->brand->slug;
            $data['brand_logo'] = $this->brand->logo;
        }

        // if has children and not empty
         if ($this->whenLoaded('children') && $this->children->isNotEmpty()) {
            $data['children'] = FrontCategoryResource::collection($this->children);
        }

        return $data;
    }
}
