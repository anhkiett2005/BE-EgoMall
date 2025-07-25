<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingZoneResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'province_code'=> $this->province_code,
            'fee'           => (float) $this->fee,
            'is_available'  => (bool) $this->is_available,
            'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
