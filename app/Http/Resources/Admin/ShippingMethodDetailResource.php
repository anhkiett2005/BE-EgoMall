<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'estimated_time' => $this->estimated_time,
            'is_active'      => $this->is_active,
            'is_default'     => $this->is_default,
            'created_at'     => $this->created_at?->toDateTimeString(),
            'zones'          => ShippingZoneResource::collection($this->whenLoaded('zones')),
        ];
    }
}