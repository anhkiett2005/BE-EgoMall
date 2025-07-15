<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ChatHistory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GeminiChatService
{
    public function ask(string $question): string
    {
        try {
            $context = $this->getContext();

            $userId = auth('api')->check() ? auth('api')->id() : null;
            $historyText = '';

            if ($userId) {
                $historyText = $this->getRecentHistory($userId);
            }

            $fullContext = trim($context . "\n" . $historyText);

            $parts = [['text' => $fullContext], ['text' => $question]];

            $payload = [
                'contents' => [
                    ['parts' => $parts]
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
            $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$answer || !is_string($answer)) {
                Log::error('Phản hồi không hợp lệ từ Gemini: ' . json_encode($data));
                throw new ApiException('Phản hồi từ Gemini không hợp lệ hoặc thiếu dữ liệu.', 500);
            }

            if ($userId) {
                ChatHistory::create([
                    'user_id' => $userId,
                    'question' => $question,
                    'answer' => rtrim($answer),
                ]);
            }

            return rtrim($answer);
        } catch (\Exception $e) {
            Log::error('Gemini error: ' . $e->getMessage());
            throw new ApiException('Lỗi khi gọi Gemini: ' . $e->getMessage(), 500);
        }
    }


    private function getContext(): string
    {
        $context = '';
        foreach (config('chatbot.context_files', []) as $file) {
            if (file_exists($file)) {
                $context .= file_get_contents($file) . "\n";
            }
        }
        return $context;
    }

    private function getRecentHistory(?int $userId): string
    {
        if (!$userId) {
            return '';
        }

        $recentHistory = ChatHistory::query()
            ->where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get()
            ->reverse();

        $historyText = '';
        foreach ($recentHistory as $item) {
            $historyText .= "Khách: {$item->question}\nBạn đã trả lời: {$item->answer}\nHãy tiếp tục trả lời câu hỏi tiếp theo.\n";
        }

        return trim($historyText);
    }


    public function getHistory(): \Illuminate\Support\Collection
    {
        if (!auth('api')->check()) {
            return collect();
        }

        $userId = auth('api')->id();

        return ChatHistory::query()
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }
}
