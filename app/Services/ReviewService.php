<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Models\OrderDetail;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ReviewService
{
    public function create(array $data, int $userId): Review
    {
        return DB::transaction(function () use ($data, $userId) {
            $orderDetail = OrderDetail::with(['order', 'review'])->find($data['order_detail_id']);

            if (!$orderDetail) {
                throw new ApiException('Không tìm thấy chi tiết đơn hàng!', Response::HTTP_NOT_FOUND);
            }

            if ($orderDetail->order->user_id !== $userId) {
                throw new ApiException('Bạn không có quyền đánh giá đơn hàng này!', Response::HTTP_FORBIDDEN);
            }

            if ($orderDetail->order->status !== 'delivered') {
                throw new ApiException('Chỉ có thể đánh giá sau khi đơn hàng đã hoàn tất!', Response::HTTP_BAD_REQUEST);
            }

            if ($orderDetail->review) {
                throw new ApiException('Bạn đã đánh giá sản phẩm này rồi!', Response::HTTP_CONFLICT);
            }

            $review = Review::create([
                'user_id'         => $userId,
                'order_detail_id' => $data['order_detail_id'],
                'rating'          => $data['rating'],
                'comment'         => $data['comment'] ?? null,
                'is_anonymous'    => $data['is_anonymous'] ?? false,
            ]);

            if (request()->hasFile('images') && is_array(request()->file('images'))) {
                $this->uploadReviewImages(request()->file('images'), $review);
            }

            return $review;
        });
    }


    private function uploadReviewImages($files, Review $review)
    {
        foreach ($files as $file) {
            $url = Common::uploadImageToCloudinary($file, 'egomall/reviews');
            $review->images()->create([
                'image_url' => $url
            ]);
        }
    }
}