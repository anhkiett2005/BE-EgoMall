<?php

namespace App\Services;

use App\Classes\Common;
use App\Exceptions\ApiException;
use App\Models\OrderDetail;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Product;

class ReviewService
{
    public function listByProductSlug(string $slug)
    {
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            throw new ApiException('Không tìm thấy sản phẩm!', Response::HTTP_NOT_FOUND);
        }

        $reviews = $product->variants()
            ->with(['orderDetails.review.user.role', 'orderDetails.review.images', 'orderDetails.review.reply'])
            ->get()
            ->pluck('orderDetails')
            ->flatten()
            ->pluck('review')
            ->filter((function ($review) {
                return $review && $review->is_visible;
            }))
            ->sortByDesc('created_at')
            ->values();

        return $reviews;
    }

    public function findById(int $id, int $userId): Review
    {
        $review = Review::with(['user.role', 'images', 'reply.user.role'])
            ->where('user_id', $userId)
            ->find($id);

        if (!$review) {
            throw new ApiException('Không tìm thấy đánh giá!', 404);
        }

        return $review;
    }

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

    public function update(int $reviewId, array $data, int $userId): Review
    {
        return DB::transaction(function () use ($reviewId, $data, $userId) {
            $review = Review::with(['orderDetail.order', 'images'])->find($reviewId);

            if (!$review) {
                throw new ApiException('Không tìm thấy đánh giá!', Response::HTTP_NOT_FOUND);
            }

            if ($review->user_id !== $userId) {
                throw new ApiException('Bạn không có quyền cập nhật đánh giá này!', Response::HTTP_FORBIDDEN);
            }

            if ($review->orderDetail->order->status !== 'delivered') {
                throw new ApiException('Chỉ có thể cập nhật khi đơn đã hoàn tất!', Response::HTTP_BAD_REQUEST);
            }

            if ($review->is_updated) {
                throw new ApiException('Bạn chỉ được cập nhật đánh giá một lần duy nhất!', Response::HTTP_BAD_REQUEST);
            }

            $review->update([
                'rating'       => $data['rating'] ?? $review->rating,
                'comment'      => $data['comment'] ?? $review->comment,
                'is_anonymous' => $data['is_anonymous'] ?? $review->is_anonymous,
            ]);

            if (request()->hasFile('images') && is_array(request()->file('images'))) {
                foreach ($review->images as $img) {
                    if (!empty($img->image_url)) {
                        $publicId = Common::getCloudinaryPublicIdFromUrl($img->image_url);
                        if ($publicId) {
                            Common::deleteImageFromCloudinary($publicId);
                        }
                    }
                }

                $review->images()->delete();

                foreach (request()->file('images') as $file) {
                    $url = Common::uploadImageToCloudinary($file, 'egomall/reviews');
                    $review->images()->create(['image_url' => $url]);
                }
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