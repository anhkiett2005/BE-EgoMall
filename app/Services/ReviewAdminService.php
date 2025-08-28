<?php

namespace App\Services;

use App\Classes\Common;
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
            $roleName = $user->role->name ?? null;

            if (!Common::hasRole($roleName, 'staff', 'admin', 'super-admin')) {
                throw new ApiException('Cấm: Quyền truy cập bị từ chối!!', Response::HTTP_FORBIDDEN);
            }

            // chỉ cho reply khi review đã approved
            if ($review->status !== 'approved') {
                throw new ApiException('Chỉ phản hồi review đã được duyệt.', Response::HTTP_BAD_REQUEST);
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
        try {
            return DB::transaction(function () use ($reviewId, $data, $userId) {
                $review = Review::with('reply')->find($reviewId);
                if (!$review || !$review->reply) {
                    throw new ApiException('Không tìm thấy phản hồi để cập nhật!', Response::HTTP_NOT_FOUND);
                }

                $reply = $review->reply;

                $user = auth('api')->user();
                $roleName = $user->role->name ?? null;

                $isOwner = ($reply->user_id === $userId);
                $isAdmin = in_array($roleName, ['admin', 'super-admin']); // sửa tên role tại đây

                if (!$isOwner && !$isAdmin) {
                    throw new ApiException('Bạn không có quyền sửa phản hồi này!', Response::HTTP_FORBIDDEN);
                }

                $reply->update(['reply' => $data['reply']]);

                // trả về bản mới nhất kèm quan hệ nếu cần hiển thị
                return $reply->fresh(['user', 'review']);
            });
        } catch (\Exception $e) {
            logger('Log bug update review reply', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'stack_trace'   => $e->getTraceAsString(),
            ]);
            throw $e instanceof ApiException
                ? $e
                : new ApiException('Không thể cập nhật phản hồi.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    // Quản lý đánh giá
    public function list(array $filters = [])
    {
        $query = Review::with([
            'user:id,name,email,image,phone,is_active,role_id',
            'images',
            'reply.user:id,name,email,image,phone,is_active,role_id',
            'orderDetail.productVariant.product:id,name,slug'
        ])
            ->latest();

        if (!empty($filters['product_id'])) {
            $query->whereHas('orderDetail.productVariant.product', function ($q) use ($filters) {
                $q->where('id', $filters['product_id']);
            });
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'approved', 'rejected'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['has_reply'])) {
            if ($filters['has_reply'] === 'false') {
                $query->whereDoesntHave('reply');
            } elseif ($filters['has_reply'] === 'true') {
                $query->whereHas('reply');
            }
        }

        return $query->get();
    }

    public function show(int $reviewId): Review
    {
        $review = Review::with([
            'user:id,name,email,image,phone,is_active,role_id',
            'images',
            'reply.user:id,name,email,image,phone,is_active,role_id',
            'orderDetail.productVariant.product:id,name,slug'
        ])->find($reviewId);

        if (!$review) {
            throw new ApiException('Không tìm thấy đánh giá!', Response::HTTP_NOT_FOUND);
        }

        return $review;
    }



    public function updateStatus(int $reviewId, string $status): Review
    {
        if (!in_array($status, ['pending', 'approved', 'rejected'])) {
            throw new ApiException('Trạng thái không hợp lệ!', Response::HTTP_BAD_REQUEST);
        }

        $review = Review::find($reviewId);

        if (!$review) {
            throw new ApiException('Không tìm thấy đánh giá!', Response::HTTP_NOT_FOUND);
        }

        if ($review->status === $status) {
            throw new ApiException('Đánh giá đã ở trạng thái này rồi!', Response::HTTP_BAD_REQUEST);
        }

        $review->status = $status;
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
