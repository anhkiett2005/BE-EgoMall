<?php

namespace App\Http\Resources\Front;

use App\Classes\Common;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'user'          => [
                'id'    => $this->user->id,
                'name'  => $this->is_anonymous ? Common::maskName($this->user->name) : $this->user->name,
                'image' => $this->is_anonymous ? null : $this->user->image,
            ],
            'rating'        => $this->rating,
            'comment'       => $this->comment,
            'is_anonymous'  => $this->is_anonymous,
            'images'        => $this->images->pluck('image_url'),
            'status'        => $this->status,
            'reply' => $this->reply ? [
                'id'   => $this->reply->id,
                'user' => [
                    'id'   => $this->reply->user->id,
                    'name' => $this->reply->user->name,
                    'role' => $this->reply->user->role->name ?? null,
                ],
                'reply' => $this->reply->reply,
                'date'  => $this->reply->created_at->toDateTimeString(),
            ] : null,

            'created_at'    => $this->created_at->toDateTimeString(),
            'updated_at'    => $this->updated_at->toDateTimeString(),
        ];
    }
}