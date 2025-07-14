<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Review;
use App\Models\ReviewReply;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ReviewReplyService
{
    public function create(array $data, int $userId): ReviewReply
    {
        return DB::transaction(function () use ($data, $userId) {
            $review = Review::with('reply')->find($data['review_id']);

            if (!$review) {
                throw new ApiException('Không tìm thấy đánh giá!', Response::HTTP_NOT_FOUND);
            }

            if ($review->reply) {
                throw new ApiException('Đánh giá này đã được phản hồi!', Response::HTTP_BAD_REQUEST);
            }

            $user = auth('api')->user();
            if (!in_array($user->role->name, ['staff', 'admin'])) {
                throw new ApiException('Chỉ nhân viên mới được phản hồi đánh giá!', Response::HTTP_FORBIDDEN);
            }

            return ReviewReply::create([
                'review_id' => $review->id,
                'user_id'   => $userId,
                'reply'     => $data['reply'],
            ]);
        });
    }
}