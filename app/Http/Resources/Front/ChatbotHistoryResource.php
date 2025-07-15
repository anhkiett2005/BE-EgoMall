<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatbotHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'question' => $this->question,
            'answer'   => $this->answer,
            'time'     => $this->created_at->toDateTimeString(),
        ];
    }
}
