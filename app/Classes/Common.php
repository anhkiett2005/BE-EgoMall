<?php

namespace App\Classes;

use App\Exceptions\ApiException;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class Common
{

    public static function generateVariantName(string $parentName, array $options)
    {
        // Lấy ra tất cả value từ options
        $values = array_values($options);

        // Ghép các giá trị với tên cha
        $variantName = $parentName . ' - ' . implode(' - ', $values);

        return $variantName;
    }

    public static function uploadImageToCloudinary($file, ?string $folder = null): ?string
    {
        $cloudName = config('cloudinary.cloud_name');
        $uploadPreset = config('cloudinary.upload_preset');
        $apiKey = config('cloudinary.api_key');
        $uploadUrl = config('cloudinary.upload_url');
        $defaultFolder = config('cloudinary.default_folder');

        if (!$cloudName || !$uploadPreset || !$apiKey) {
            throw new ApiException('Thiếu thông tin cấu hình Cloudinary', 500);
        }

        $targetFolder = $folder ?? $defaultFolder;

        $response = Http::asMultipart()->post($uploadUrl, [
            ['name' => 'file', 'contents' => fopen($file->getPathname(), 'r')],
            ['name' => 'upload_preset', 'contents' => $uploadPreset],
            ['name' => 'api_key', 'contents' => $apiKey],
            ['name' => 'folder', 'contents' => $targetFolder],
        ]);

        if (!$response->successful()) {
            throw new ApiException('Upload ảnh thất bại', 500, [$response->body()]);
        }

        $data = $response->json();

        if (!isset($data['secure_url'])) {
            throw new ApiException('Upload ảnh thất bại: thiếu secure_url', 500, [$data]);
        }

        return $data['secure_url'];
    }

    public static function deleteImageFromCloudinary(string $publicId): bool
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            throw new ApiException('Thiếu thông tin cấu hình Cloudinary', 500);
        }

        $timestamp = time();
        $signature = sha1("public_id={$publicId}&timestamp={$timestamp}{$apiSecret}");

        $response = Http::asForm()->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
            'public_id' => $publicId,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        if (!$response->successful()) {
            throw new ApiException('Xóa ảnh thất bại', 500, [$response->body()]);
        }

        return $response->json()['result'] === 'ok';
    }

    public static function getCloudinaryPublicIdFromUrl(string $url): ?string
    {
        if (empty($url)) return null;

        $matches = [];
        if (preg_match('#/upload/(?:v\d+/)?(.+?)\.[a-zA-Z]+$#', $url, $matches)) {
            return $matches[1] ?? null;
        }

        return null;
    }

    public static function formatCategoryWithChildren($category)
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'thumbnail' => $category->thumbnail,
            'is_active' => $category->is_active,
            'is_featured' => $category->is_featured,
            'type' => $category->type,
            'options' => $category->categoryOptions->map(function ($categoryOption) {
                return [
                    'id' => $categoryOption->variantOption->id ?? null,
                    'name' => $categoryOption->variantOption->name ?? null,
                ];
            }),
            'children' => $category->children->map(function ($child) {
                return self::formatCategoryWithChildren($child); // <--- đệ quy tại đây
            }),
            'created_at' => $category->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $category->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public static function generateUploadToken()
    {
        $secretKey = env('UPLOAD_IMAGE_TOKEN');

        $payload = [
            'purpose' => 'upload_image',
            'timestamp' => now()->timestamp, // thêm timestamp để tạo token khác nhau mỗi lần
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $secretKey);

        return base64_encode($payloadJson) . '.' . $signature;
    }

    public static function isValidUploadToken(string $token): bool
    {
        $secretKey = env('UPLOAD_IMAGE_TOKEN');

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }

        [$encodedPayload, $signature] = $parts;

        $payloadJson = base64_decode($encodedPayload);
        $expectedSignature = hash_hmac('sha256', $payloadJson, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    public static function formatDateVN(?Carbon $date): ?string
    {
        return $date?->timezone('Asia/Ho_Chi_Minh')->toDateTimeString();
    }


    public static function generateUniqueId($orderId)
    {
        $salt = env('BCRYPT_ROUNDS');
        $hash = hash_hmac('sha256', $orderId,$salt);
        return 'ORD-'. strtoupper(substr($hash, 0, 10));
    }

    public static function calculateOrderStock(Order $order)
    {
        // Lấy chi tiết đơn hàng không phải là quà tặng
        $orderDetails = $order->details()->where('is_gift',false)->get();

        // Lấy ra số lượng theo variant_id
         $quantities = $orderDetails->groupBy('product_variant_id')->map(function ($group) {
            return $group->sum('quantity');
        });

        // Trừ tồn kho
        foreach ($quantities as $variantId => $qty) {
            ProductVariant::where('id', $variantId)->decrement('quantity', $qty);
        }
    }

    public static function momoPayment($orderId, $amount)
    {

        try {
            if (!$amount || $amount <= 0) {
                throw new ApiException("Số tiền thanh toán không hợp lệ!");
            }

            $partnerCode = env('MOMO_PARTNER_CODE');
            $accessKey = env('MOMO_ACCESS_KEY');
            $secretKey = env('MOMO_SECRET_KEY');
            $orderInfo = "Thanh toán đơn hàng qua ATM MoMo";
            $redirectUrl = route('payment.momo.redirect'); // ví dụ: định nghĩa route trả về sau thanh toán
            $ipnUrl =  'https://e819740b81e9.ngrok-free.app/api/v1/front/payment/momo/ipn';     //route('payment.momo.ipn'); // ví dụ: route nhận callback IPN
            $requestId = now()->timestamp . '';
            $requestType = 'payWithATM';
            $extraData = '';

            // Build raw signature string
            $rawHash = "accessKey=$accessKey&amount=$amount&extraData=$extraData&ipnUrl=$ipnUrl&orderId=$orderId&orderInfo=$orderInfo&partnerCode=$partnerCode&redirectUrl=$redirectUrl&requestId=$requestId&requestType=$requestType";
            $signature = hash_hmac("sha256", $rawHash, $secretKey);

            $body = [
                'partnerCode' => $partnerCode,
                'partnerName' => "Test",
                'storeId' => "MomoTestStore",
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $redirectUrl,
                'ipnUrl' => $ipnUrl,
                'lang' => 'vi',
                'extraData' => $extraData,
                'requestType' => $requestType,
                'signature' => $signature
            ];

            $response = Http::post('https://test-payment.momo.vn/v2/gateway/api/create', $body);

            if ($response->failed()) {
                throw new ApiException("Gửi yêu cầu thanh toán MoMo thất bại!");
            }

            $jsonResult = $response->json();

            if (!isset($jsonResult['payUrl'])) {
                throw new ApiException("Không nhận được liên kết thanh toán MoMo!");
            }

            // return redirect()->to($jsonResult['payUrl']);
            return $jsonResult['payUrl'];
        } catch(\Exception $e) {
            throw new ApiException($e->getMessage());
        }
    }


}
