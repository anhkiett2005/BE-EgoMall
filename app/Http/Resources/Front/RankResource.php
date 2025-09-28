<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'amount_to_point' => $this->amount_to_point,
            'min_spent_amount' => $this->min_spent_amount,
            'converted_amount' => $this->converted_amount,
            'discount' => $this->discount,
            'maximum_discount_order' => $this->maximum_discount_order,
            'minimum_point' => $this->minimum_point,
            'maintenance_point' => $this->maintenance_point,
            'point_limit_transaction' => $this->point_limit_transaction,
            'status_payment_point' => $this->status_payment_point
        ];
    }
}
