<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'user'          => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'image' => $this->user->image,
            ],
            'product' => optional($this->orderDetail?->productVariant?->product)?->only(['id', 'name', 'slug']),
            'rating'        => $this->rating,
            'comment'       => $this->comment,
            'is_anonymous'  => $this->is_anonymous,
            'images'        => $this->images->pluck('image_url'),
            'status'        => $this->status,
            'reply' => $this->reply ? [
                'id'     => $this->reply->id,
                'user'   => [
                    'id'   => $this->reply->user->id,
                    'name' => $this->reply->user->name,
                    'role' => $this->reply->user->role->name ?? null,
                ],
                'reply'  => $this->reply->reply,
                'date'   => $this->reply->created_at->toDateTimeString(),
            ] : null,

            'created_at'    => $this->created_at->toDateTimeString(),
            'updated_at'    => $this->updated_at->toDateTimeString(),
        ];
    }
}