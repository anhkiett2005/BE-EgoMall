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
            'reply'         => $this->replies ? [
                'id'     => $this->replies->first()->id,
                'user'   => [
                    'id'   => $this->replies->first()->user->id,
                    'name' => $this->replies->first()->user->name,
                    'role' => $this->replies->first()->user->role->name ?? null,
                ],
                'reply'  => $this->replies->first()->reply,
                'date'   => $this->replies->first()->created_at->toDateTimeString(),
            ] : null,
            'created_at'    => $this->created_at->toDateTimeString(),
        ];
    }
}