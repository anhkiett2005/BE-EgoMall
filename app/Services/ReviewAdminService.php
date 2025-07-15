<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Review;
use App\Models\ReviewReply;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ReviewAdminService
{
    // staff phản hồi đánh giá
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
            if (!in_array($user->role->name, ['staff', 'admin', 'super_admin'])) {
                throw new ApiException('Chỉ nhân viên mới được phản hồi đánh giá!', Response::HTTP_FORBIDDEN);
            }

            return ReviewReply::create([
                'review_id' => $review->id,
                'user_id'   => $userId,
                'reply'     => $data['reply'],
            ]);
        });
    }

    public function update(int $reviewId, array $data, int $userId): ReviewReply
    {
        return DB::transaction(function () use ($reviewId, $data, $userId) {
            $review = Review::with('reply')->find($reviewId);

            if (!$review || !$review->reply) {
                throw new ApiException('Không tìm thấy phản hồi để cập nhật!', Response::HTTP_NOT_FOUND);
            }

            $reply = $review->reply;

            // Kiểm tra người sửa phải là người đã phản hồi, hoặc có quyền admin
            $user = auth('api')->user();
            $canEdit = $reply->user_id === $userId || in_array($user->role->name, ['admin', 'super_admin']);

            if (!$canEdit) {
                throw new ApiException('Bạn không có quyền sửa phản hồi này!', Response::HTTP_FORBIDDEN);
            }

            $reply->update([
                'reply' => $data['reply'],
            ]);

            return $reply;
        });
    }



    // Quản lý đánh giá
    public function list(array $filters = [])
    {
        $query = Review::with([
            'user:id,name,email,image',
            'images',
            'reply.user:id,name',
            'orderDetail.productVariant.product:id,name,slug'
        ])
            ->latest();

        if (!empty($filters['product_id'])) {
            $query->whereHas('orderDetail.productVariant.product', function ($q) use ($filters) {
                $q->where('id', $filters['product_id']);
            });
        }

        if (isset($filters['is_visible'])) {
            $query->where('is_visible', filter_var($filters['is_visible'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['has_reply']) && $filters['has_reply'] === 'false') {
            $query->whereDoesntHave('reply');
        }

        return $query->get();
    }


    public function toggleVisibility(int $reviewId): Review
    {
        $review = Review::find($reviewId);

        if (!$review) {
            throw new ApiException('Không tìm thấy đánh giá!', Response::HTTP_NOT_FOUND);
        }

        $review->is_visible = !$review->is_visible;
        $review->save();

        return $review;
    }

    public function delete(int $reviewId): void
    {
        $review = Review::find($reviewId);

        if (!$review) {
            throw new ApiException('Không tìm thấy đánh giá!', Response::HTTP_NOT_FOUND);
        }

        $review->delete();
    }
}