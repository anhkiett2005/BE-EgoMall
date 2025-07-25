<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'estimated_time' => $this->estimated_time,
            'is_active'      => (bool) $this->is_active,
            'is_default'     => (bool) $this->is_default,
            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}