<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $this->resource->load(['role', 'role.permissions']);
        $data = [
            'name'    => $this->name,
            'email'   => $this->email,
            'phone'   => $this->phone,
            'image'   => $this->image,
            'role'    => $this->role->name,
        ];

        if ($this->role->name !== 'customer') {
            $data['permissions'] = $this->role->permissions->pluck('name')->toArray();
        }

        return $data;
    }
}
