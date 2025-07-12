<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'min_order_value' => (float) $this->min_order_value,
            'max_discount' => $this->max_discount ? (float) $this->max_discount : null,
            'usage_limit' => $this->usage_limit,
            'discount_limit' => $this->discount_limit,
            'start_date' => optional($this->start_date)->format('Y-m-d H:i:s'),
            'end_date' => optional($this->end_date)->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
