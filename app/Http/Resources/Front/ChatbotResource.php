<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatbotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'question' => $this['question'],
            'answer'   => $this['answer'],
        ];
    }
}
