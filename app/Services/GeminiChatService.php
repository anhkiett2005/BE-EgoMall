<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ChatHistory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GeminiChatService
{
    public function ask(string $question): string
    {
        try {
            // 1. Đọc context từ các file txt
            $context = $this->getContext();

            // 2. Lấy 5 câu hỏi gần nhất từ DB (nếu có)
            $userId = auth('api')->check() ? auth('api')->id() : null;

            Session::put('chat_session_active', true);
            $sessionId = Session::getId();
            Log::info('Session từ header: ' . request()->header('Cookie'));
            Log::info('Session Laravel nhận được: ' . Session::getId());

            $recentHistory = ChatHistory::query()
                ->where(function ($q) use ($userId, $sessionId) {
                    if ($userId) {
                        $q->where('user_id', $userId);
                    } else {
                        $q->where('session_id', $sessionId);
                    }
                })
                ->latest()
                ->take(5)
                ->get()
                ->reverse(); // đảo ngược để câu cũ xếp trước

            $historyText = '';
            foreach ($recentHistory as $item) {
                $historyText .= "Khách: {$item->question}\nAI: {$item->answer}\n";
            }

            // 3. Ghép context gốc + history + câu hỏi mới
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

            // 4. Lưu lại
            ChatHistory::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'question' => $question,
                'answer' => rtrim($answer),
            ]);

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
}
