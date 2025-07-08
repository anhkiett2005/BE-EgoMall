<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_address' => $this->full_address,
            'address_detail' => $this->address_detail,
            'province' => [
                'code' => $this->province->code,
                'name' => $this->province->full_name,
            ],
            'district' => [
                'code' => $this->district->code,
                'name' => $this->district->full_name,
            ],
            'ward' => [
                'code' => $this->ward->code,
                'name' => $this->ward->full_name,
            ],
            'receiver' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
            ],
            'address_name' => $this->address_name,
            'note' => $this->note,
            'is_default' => $this->is_default,
        ];
    }
}
