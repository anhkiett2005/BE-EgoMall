<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'fee'            => (float) optional($this->zones->first())->fee,
            'estimated_time' => $this->estimated_time,
        ];
    }
}