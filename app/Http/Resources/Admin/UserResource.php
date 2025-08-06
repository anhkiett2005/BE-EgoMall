<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified_at' => optional($this->email_verified_at)->format('d-m-Y H:i:s'),
            'image' => $this->image,
            'role' => [
                'id' => $this->role->id ?? null,
                'name' => $this->role->name ?? null,
                'display_name' => $this->role->display_name ?? null,
            ],
            'is_active' => $this->is_active,
            'created_at' => optional($this->created_at)->format('d-m-Y H:i:s'),
            'updated_at' => optional($this->updated_at)->format('d-m-Y H:i:s'),
        ];
    }
}