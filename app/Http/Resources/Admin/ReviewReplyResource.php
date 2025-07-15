<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'reply'     => $this->reply,
            'review_id' => $this->review_id,
            'user'      => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
