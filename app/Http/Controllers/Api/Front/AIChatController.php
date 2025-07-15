<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Front\ChatbotHistoryResource;
use App\Http\Resources\Front\ChatbotResource;
use App\Response\ApiResponse;
use App\Services\GeminiChatService;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    public function chat(Request $request)
{
    $question = trim($request->input('message'));

    if (!$question) {
        throw new ApiException('Bạn chưa nhập câu hỏi!', 422);
    }

    if (mb_strlen($question) > 1000) {
        throw new ApiException('Câu hỏi quá dài, vui lòng rút gọn dưới 1000 ký tự!', 422);
    }

    $answer = app(GeminiChatService::class)->ask($question);

    return ApiResponse::success(
        'Trả lời thành công',
        200,
        (new ChatbotResource([
            'question' => $question,
            'answer' => $answer,
        ]))->resolve()
    );
}


    public function history(Request $request)
    {
        if (!auth('api')->check()) {
            return ApiResponse::success('Lịch sử hội thoại', 200, []);
        }

        $histories = app(GeminiChatService::class)->getHistory();

        return ApiResponse::success(
            'Lịch sử hội thoại',
            200,
            ChatbotHistoryResource::collection($histories)->resolve()
        );
    }
}
