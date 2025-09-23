<?php

namespace App\Classes;

use App\Exceptions\ApiException;
use App\Jobs\SendOrderStatusMailJob;
use App\Jobs\SendPromotionMailJob;
use App\Jobs\SendReturnApprovedMailJob;
use App\Jobs\SendSetPasswordMailJob;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\User;
use App\Response\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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
            'parent_id' => $category->parent_id,
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
        $hash = hash_hmac('sha256', $orderId, $salt);
        return 'ORD-' . strtoupper(substr($hash, 0, 10));
    }

    public static function calculateOrderStock(Order $order)
    {
        // Lấy tất cả chi tiết đơn hàng (bao gồm sản phẩm & quà tặng)
        $orderDetails = $order->details;

        if ($orderDetails->isEmpty()) return;

        // Gom nhóm theo variant_id và tính tổng số lượng
        $quantities = $orderDetails->groupBy('product_variant_id')->map(function ($group) {
            return $group->sum('quantity');
        });

        // Trừ tồn kho tương ứng
        foreach ($quantities as $variantId => $qty) {
            ProductVariant::where('id', $variantId)->decrement('quantity', $qty);
        }
    }

    public static function generateCodTransactionId()
    {
        return 'COD-' . strtoupper(Str::random(10));
    }

    public static function generateCodRefundTransactionId()
    {
        return 'REFUND-COD-' . strtoupper(Str::random(10));
    }

    public static function restoreOrderStock(Order $order)
    {
        $orderDetails = $order->details;

        if ($orderDetails->isEmpty()) return;

        $quantities = $orderDetails->groupBy('product_variant_id')->map(function ($group) {
            return $group->sum('quantity');
        });

        foreach ($quantities as $variantId => $qty) {
            ProductVariant::where('id', $variantId)->increment('quantity', $qty);
        }
    }


    public static function momoPayment(Order $order)
    {

        try {
            // orderId phải UNIQUE cho mỗi request
            $orderId = $order->unique_id;

            $amount = $order->total_price;


            if (!$amount || $amount <= 0) {
                throw new ApiException("Số tiền thanh toán không hợp lệ!");
            }

            $partnerCode = env('MOMO_PARTNER_CODE');
            $accessKey = env('MOMO_ACCESS_KEY');
            $secretKey = env('MOMO_SECRET_KEY');
            $orderInfo = "Thanh toán đơn hàng qua MoMo";
            $redirectUrl = route('payment.momo.redirect'); // ví dụ: định nghĩa route trả về sau thanh toán
            $ipnUrl =  route('payment.momo.ipn');  //'https://18667f599642.ngrok-free.app/api/v1/front/payment/momo/ipn';       // ví dụ: route nhận callback IPN
            $requestId = now()->timestamp . '';
            $requestType = 'captureWallet';
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

            // Lưu lại thời gian tạo thanh toán
            $order->payment_created_at = Carbon::now();
            $order->save();


            return $jsonResult['payUrl'];
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage());
        }
    }


    public static function refundMomoTransaction($transId, $amount)
    {
        try {
            if (!$amount || $amount <= 0) {
                throw new ApiException("Số tiền hoàn không hợp lệ!");
            }

            $partnerCode = env('MOMO_PARTNER_CODE');
            $accessKey = env('MOMO_ACCESS_KEY');
            $secretKey = env('MOMO_SECRET_KEY');
            $refundEndpoint = 'https://test-payment.momo.vn/v2/gateway/api/refund'; // Sửa nếu dùng production

            $orderId = 'REFUND_' . Str::uuid(); // Tạo orderId riêng cho refund
            $requestId = (string) Str::uuid();  // requestId là duy nhất
            $lang = 'vi';
            $description = "Hoàn tiền đơn hàng #" . $orderId;

            // Tạo chuỗi để ký
            $rawHash = "accessKey=$accessKey&amount=$amount&description=$description"
                . "&orderId=$orderId&partnerCode=$partnerCode"
                . "&requestId=$requestId&transId=$transId";

            $signature = hash_hmac("sha256", $rawHash, $secretKey);

            $payload = [
                'partnerCode' => $partnerCode,
                'orderId' => $orderId,
                'requestId' => $requestId,
                'amount' => (int) $amount,
                'transId' => (int) $transId,
                'lang' => $lang,
                'description' => $description,
                'signature' => $signature
            ];

            $response =  Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($refundEndpoint, $payload);

            $result = $response->json();
            // logger('Refund result:', $result);

            if ($response->failed()) {
                throw new ApiException("Refund thất bại: " . json_encode($result));
            }

            return $result;
        } catch (\Exception $e) {
            throw new ApiException("Lỗi hoàn tiền MoMo: " . $e->getMessage());
        }
    }

    public static function refundVnPayTransaction($params = [])
    {
        try {
            $vnp_Url = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";
            $vnp_TmnCode = env('VNP_TMN_CODE');
            $vnp_HashSecret = env('VNP_HASH_SECRECT_KEY');
            $vnp_Version = '2.1.0';
            $vnp_Command = 'refund';
            $vnp_TransactionType = $params['transaction_type'] ?? "03"; // 02 = hoàn toàn phần, 03 = hoàn 1 phần
            $vnp_TxnRef = $params['txn_ref']; // Mã đơn hàng hệ thống của merchant
            $vnp_TransactionNo = $params['transaction_no']; // Có thể bỏ qua
            $vnp_Amount = (int) ($params['amount'] * 100); // Nhân 100 theo chuẩn VNPAY
            $vnp_OrderInfo = $params['order_info'];
            $vnp_CreateBy = $params['create_by'];
            $vnp_IpAddr = $params['ip_addr'] ?? request()->ip();

            // Ngày giờ định dạng yyyyMMddHHmmss
            $vnp_TransactionDate = $params['transaction_date']; // yyyyMMddHHmmss: lấy từ lúc giao dịch thanh toán
            $vnp_CreateDate = now()->format('YmdHis');
            $vnp_RequestId = Str::uuid(); // mã duy nhất mỗi lần refund

            // Chuỗi dữ liệu để hash
            $hashData = implode('|', [
                $vnp_RequestId,
                $vnp_Version,
                $vnp_Command,
                $vnp_TmnCode,
                $vnp_TransactionType,
                $vnp_TxnRef,
                $vnp_Amount,
                $vnp_TransactionNo,
                $vnp_TransactionDate,
                $vnp_CreateBy,
                $vnp_CreateDate,
                $vnp_IpAddr,
                $vnp_OrderInfo
            ]);

            $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

            $body = [
                'vnp_RequestId'       => $vnp_RequestId,
                'vnp_Version'         => $vnp_Version,
                'vnp_Command'         => $vnp_Command,
                'vnp_TmnCode'         => $vnp_TmnCode,
                'vnp_TransactionType' => $vnp_TransactionType,
                'vnp_TxnRef'          => $vnp_TxnRef,
                'vnp_Amount'          => $vnp_Amount,
                'vnp_TransactionNo'   => $vnp_TransactionNo,
                'vnp_TransactionDate' => $vnp_TransactionDate,
                'vnp_CreateBy'        => $vnp_CreateBy,
                'vnp_CreateDate'      => $vnp_CreateDate,
                'vnp_IpAddr'          => $vnp_IpAddr,
                'vnp_OrderInfo'       => $vnp_OrderInfo,
                'vnp_SecureHash'      => $vnp_SecureHash
            ];

            // logger('Log refund VNPAY', $body);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($vnp_Url, $body);

            return $response->json();
        } catch (\Exception $e) {
            logger('Log refund VNPAY', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException("Lỗi hoàn tiền VNPAY: " . $e->getMessage());
        }
    }

    public static function refundZaloPayTransaction($params = [])
    {
        try {
            $zp_Url = "https://sb-openapi.zalopay.vn/v2/refund";
            $zp_MRefundId = $params['m_refund_id'];
            $zp_AppId = (int) $params['app_id'];
            $zp_TransId = $params['zp_trans_id'];
            $zp_Amount = $params['amount'];
            $zp_TimeStamp = $params['timestamp'];
            $zp_Description = $params['description'];

            // Tạo mac
            $hashData = implode('|', [
                $zp_AppId,
                $zp_TransId,
                $zp_Amount,
                $zp_Description,
                $zp_TimeStamp
            ]);

            $zp_Mac = hash_hmac('sha256', $hashData, $params['zp_key1']);

            $body = [
                'm_refund_id' => $zp_MRefundId,
                'app_id' => $zp_AppId,
                'zp_trans_id' => $zp_TransId,
                'amount' => $zp_Amount,
                'timestamp' => $zp_TimeStamp,
                'mac' => $zp_Mac,
                'description' => $zp_Description
            ];

            // dd($body);

            $response = Http::asJson()->post($zp_Url, $body);

            return $response->json();
        } catch (\Exception $e) {
            logger('Log refund ZaloPay', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException("Lỗi hoàn tiền ZaloPay: " . $e->getMessage());
        }
    }

    public static function maskName(string $name): string
    {
        $words = explode(' ', $name);
        $masked = [];

        foreach ($words as $word) {
            $len = mb_strlen($word);
            if ($len <= 2) {
                $masked[] = $word;
            } else {
                $first = mb_substr($word, 0, 1);
                $last = mb_substr($word, -1, 1);
                $masked[] = $first . str_repeat('*', $len - 2) . $last;
            }
        }

        return implode(' ', $masked);
    }

    public static function validateProductAndVariantConflicts(
        Collection $productIds,
        Collection $variantIds,
        ?int $promotionId = null
    ): void {
        // Lấy product_id của các variant trong DB nếu đang thêm mới vào promotion cũ
        $existingVariantProductIds = collect();

        if ($promotionId !== null) {
            $existingVariantIds = DB::table('promotion_product')
                ->where('promotion_id', $promotionId)
                ->whereNotNull('product_variant_id')
                ->pluck('product_variant_id');

            if ($existingVariantIds->isNotEmpty()) {
                $existingVariantProductIds = DB::table('product_variants')
                    ->whereIn('id', $existingVariantIds)
                    ->pluck('product_id')
                    ->unique();
            }

            // Check: nếu thêm mới product_id mà trùng với các product_id đã có từ variant -> conflict
            $conflict1 = $productIds->intersect($existingVariantProductIds);
            if ($conflict1->isNotEmpty()) {
                throw new ApiException(
                    'Xung đột: đã có biến thể thuộc sản phẩm ID: ' . $conflict1->implode(', ') . ' trong khuyến mãi.',
                    Response::HTTP_CONFLICT
                );
            }

            // Check: nếu thêm mới variant_id mà product_id của nó trùng với product_id đã có trong DB
            $existingProductIds = DB::table('promotion_product')
                ->where('promotion_id', $promotionId)
                ->whereNotNull('product_id')
                ->pluck('product_id')
                ->unique();

            if ($variantIds->isNotEmpty()) {
                $newVariantProductIds = DB::table('product_variants')
                    ->whereIn('id', $variantIds)
                    ->pluck('product_id')
                    ->unique();

                $conflict2 = $newVariantProductIds->intersect($existingProductIds);
                if ($conflict2->isNotEmpty()) {
                    throw new ApiException(
                        'Xung đột: bạn đang thêm biến thể thuộc sản phẩm đã áp dụng khuyến mãi ID: ' . $conflict2->implode(', '),
                        Response::HTTP_CONFLICT
                    );
                }
            }
        }

        // Check xung đột nội bộ trong request như cũ
        if ($productIds->isNotEmpty() && $variantIds->isNotEmpty()) {
            $variantProductIds = DB::table('product_variants')
                ->whereIn('id', $variantIds)
                ->pluck('product_id')
                ->unique();

            $conflictInRequest = $productIds->intersect($variantProductIds);

            if ($conflictInRequest->isNotEmpty()) {
                throw new ApiException(
                    'Xung đột nội bộ: Bạn đang áp dụng cho cả sản phẩm và biến thể con (ID: ' . $conflictInRequest->implode(', ') . ')',
                    Response::HTTP_CONFLICT
                );
            }
        }
    }

    public static function validateDiscountOnSaleVariants(Collection $productIds, Collection $variantIds, string $promotionType)
    {
        // Chỉ kiểm tra với loại phần trăm hoặc cố định
        if (!in_array($promotionType, ['percentage', 'fixed_amount'])) {
            return;
        }

        $query = ProductVariant::query();

        // Lấy các variant của những product_id được chọn
        if ($productIds->isNotEmpty()) {
            $variants = (clone $query)->whereIn('product_id', $productIds->toArray())
                ->whereNotNull('sale_price')
                ->get();

            if ($variants->isNotEmpty()) {
                throw new ApiException('Không thể áp dụng khuyến mãi cho sản phẩm có biến thể đang giảm giá!!');
            }
        }

        // Kiểm tra các variant được áp dụng trực tiếp
        if ($variantIds->isNotEmpty()) {
            $variants = (clone $query)->whereIn('id', $variantIds->toArray())
                ->whereNotNull('sale_price')
                ->get();

            if ($variants->isNotEmpty()) {
                throw new ApiException('Không thể áp dụng khuyến mãi cho biến thể đang giảm giá !!');
            }
        }
    }



    public static function syncApplicableProducts(Promotion $promotion, array $items): void
    {
        $productIds = collect();
        $variantIds = collect();

        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $productIds->push($item['product_id']);
            }
            if (!empty($item['variant_id'])) {
                $variantIds->push($item['variant_id']);
            }
        }

        if ($productIds->isNotEmpty()) {
            $promotion->products()->sync($productIds->filter()->unique());
        }

        if ($variantIds->isNotEmpty()) {
            $promotion->productVariants()->sync($variantIds->filter()->unique());
        }
    }

    public static function getLeafCategoryIds($category): array
    {
        $ids = [];

        foreach ($category->children as $child) {
            if ($child->children->isEmpty()) {
                $ids[] = $child->id;
            } else {
                $ids = array_merge($ids, self::getLeafCategoryIds($child));
            }
        }

        return $ids;
    }

    public static function hasRole($roleName, ...$allowedRoles)
    {
        return in_array($roleName, $allowedRoles);
    }

    public static function sendOrderStatusMail(Order $order, string $status): void
    {
        try {
            SendOrderStatusMailJob::dispatch($order, $status);
        } catch (\Throwable $e) {
            logger()->error("Gửi mail thất bại (Order ID: {$order->id}) - Status: {$status} - Lỗi: {$e->getMessage()}");
        }
    }

    public static function sendSetPasswordMail(User $user, string $roleName): void
    {
        try {
            SendSetPasswordMailJob::dispatch($user, $roleName);
        } catch (\Throwable $e) {
            logger()->error("Gửi mail thiết lập mật khẩu thất bại (User ID: {$user->id}) - Lỗi: {$e->getMessage()}");
        }
    }

    public static function sendPromotionEmails(Promotion $promotion): void
    {
        try {
            User::where('role_id', 4)
                ->where('is_active', true)
                ->whereNotNull('email_verified_at')
                ->chunk(100, function ($customers) use ($promotion) {
                    foreach ($customers as $customer) {
                        dispatch(new SendPromotionMailJob($customer, $promotion));
                    }
                });
        }catch (\Exception $e) {
            Log::channel('promotion')->error("Gửi mail thất bại khi kích hoạt promotion",[
                'promotion_id' => $promotion->id,
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile(),
            ]);
        }
    }

    public static function sendReturnApprovedMail(Order $order, bool $afterCommit = false): void
    {
        try {
            $job = SendReturnApprovedMailJob::dispatch($order->id);

            if ($afterCommit) {
                $job->afterCommit();
            }
        } catch (\Throwable $e) {
            logger()->error("Gửi mail hoàn trả thất bại (Order ID: {$order->id}) - Lỗi: {$e->getMessage()}");
        }
    }

    public static function revertVoucherUsageInline(Order $order): void
    {
        if (!$order->coupon_id) return;

        // +1 lại usage_limit nếu có giới hạn
        $coupon = Coupon::where('id', $order->coupon_id)->lockForUpdate()->first();
        if ($coupon) {
            if (!is_null($coupon->usage_limit)) {
                $coupon->increment('usage_limit');
            }

            // Nếu bảng coupon_usages có order_id thì xóa đúng bản ghi của đơn này
            if(!is_null($coupon->discount_limit)) {
                if (Schema::hasColumn('coupon_usages', 'order_id')) {
                    CouponUsage::where('coupon_id', $coupon->id)
                        ->where('user_id', $order->user_id)
                        ->where('order_id', $order->id)
                        ->delete();
                } else {
                    // Fallback: xóa 1 usage gần nhất của user+coupon (kém chính xác hơn)
                    $usage = CouponUsage::where('coupon_id', $coupon->id)
                        ->where('user_id', $order->user_id)
                        ->latest('id')->first();
                    if ($usage) $usage->delete();
                }
            }
        }
    }

    public static function validateSignature($requestData, $vnp_HashSecret)
    {
        $vnp_SecureHash = $requestData['vnp_SecureHash'];
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        return $secureHash === $vnp_SecureHash;
    }


    public static function validateSignatureFromJson(array $response)
    {
        $vnp_HashSecret = env('VNP_HASH_SECRECT_KEY');
        $vnp_SecureHash = $response['vnp_SecureHash'] ?? '';

        // Đảm bảo thứ tự đúng như VNPAY yêu cầu
        $fields = [
            'vnp_ResponseId',
            'vnp_Command',
            'vnp_ResponseCode',
            'vnp_Message',
            'vnp_TmnCode',
            'vnp_TxnRef',
            'vnp_Amount',
            'vnp_BankCode',
            'vnp_PayDate',
            'vnp_TransactionNo',
            'vnp_TransactionType',
            'vnp_TransactionStatus',
            'vnp_OrderInfo'
        ];

        $data = [];
        foreach ($fields as $field) {
            $data[] = $response[$field] ?? '';
        }

        $hashData = implode('|', $data);
        // logger('hashData', [$hashData]);
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        return $secureHash === $vnp_SecureHash;
    }

    public static function getActivePromotion()
    {
        $now = now();

        // Cache lại kết quả để optimize perfomance
        return Cache::remember('active_promotions', 60, function () use ($now) {
            return Promotion::with(['products', 'productVariants'])
            ->where('status', '!=', 0)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();
        });
    }

    public static function checkPromotion($variant, $promotion)
    {
        if (!$promotion) return null;

        if ($promotion->productVariants->contains('id', $variant->id)
            || $promotion->products->contains('id', $variant->product_id)) {
            return self::calculateDiscountAfterPromotion($variant, $promotion);
        }

        return null;
    }

    // Hàm xử lý tính toán giảm giá khi áp dụng khuyên mãi
    public static function calculateDiscountAfterPromotion($variant, $promotion)
    {

       // Nếu đã có sale_price hoặc là buy_get → không tính
        if ($variant->sale_price !== null || $promotion->promotion_type === 'buy_get') {
            return null;
        }

        $price = $variant->price;
        $discount = 0;

        if ($promotion->promotion_type === 'percentage') {
            $discount = $price * ($promotion->discount_value / 100);
        } elseif ($promotion->promotion_type === 'fixed_amount') {
            $discount = $promotion->discount_value;
        }

        return max(0, $price - $discount);
    }


    public static function respondWithToken($token)
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Carbon::now()->addMinutes(config('jwt.ttl'))->timestamp
        ];
    }
}
