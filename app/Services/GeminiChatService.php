<?php

namespace App\Services;

use App\Exceptions\ApiException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GeminiChatService
{
    public function ask(string $question): string
    {
        try {
            $context = '';
            foreach (config('chatbot.context_files', []) as $file) {
                if (file_exists($file)) {
                    $context .= file_get_contents($file) . "</br>";
                }
            }

            $parts = [];
            if (!empty($context)) {
                $parts[] = ['text' => $context];
            }
            $parts[] = ['text' => $question];

            $payload = [
                'contents' => [
                    [
                        'parts' => $parts
                    ]
                ]
            ];

            $client = new Client();

            $response = $client->post(
                config('chatbot.endpoint'),
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'X-goog-api-key' => config('chatbot.api_key'),
                    ],
                    'json' => $payload,
                    'timeout' => 15,
                ]
            );

            $data = json_decode($response->getBody(), true);
            if (
                !isset($data['candidates'][0]['content']['parts'][0]['text'])
            ) {
                throw new ApiException('Phản hồi từ Gemini không hợp lệ.', 500);
            }
            return rtrim($data['candidates'][0]['content']['parts'][0]['text']);
        } catch (\Exception $e) {
            Log::error('Gemini error: ' . $e->getMessage());
            throw new ApiException('Lỗi khi gọi Gemini: ' . $e->getMessage(), 500);
        }
    }
}
