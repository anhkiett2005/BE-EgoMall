<?php

namespace App\Http\Controllers\Api\Front;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SystemSetting;
use App\Response\ApiResponse;
use Illuminate\Support\Str;
use PayOS\PayOS;
use Symfony\Component\HttpFoundation\Response;

class PayOsController extends Controller
{

    protected $payOs;

    public function __construct(PayOs $payOs)
    {
        $this->payOs = $payOs;
    }

    public function processPayment(Order $order)
    {
        try {

            $address = SystemSetting::where('setting_key', 'site_address')
                                    ->value('setting_value');

            if(empty($address)) {
                throw new ApiException('Có lỗi xảy ra, vui lòng liên hệ administrator!!', Response::HTTP_BAD_REQUEST);
            }

            // load order_detals + product
            $order->load('details.productVariant');

            // cbi items mua hàng
            $items = $order->details->map(function ($item) {
                return [
                    'name' => $item->productVariant->variant_name,
                    'quantity' => $item->quantity,
                    'price' => $item->sale_price ?: $item->price
                ];
            })->toArray();

            // config hóa đơn gửi sang payOs
            $invoice = [
                'buyerNotGetInvoice' => true
            ];

            $data = [
                'orderCode' => (int) ($order->id . now()->format('His')),
                'amount' => $order->total_price,
                'description' => Str::limit('Giao dich ' . $order->unique_id, 25, ''),
                'buyerName' => $order->shipping_name,
                'buyerCompanyName' => 'EgoMall',
                'buyerAddress' => $address,
                'buyerEmail' => $order->shipping_email,
                'buyerPhone' => $order->shipping_phone,
                'items' => $items,
                'cancelUrl' => config('services.frontend_url') . "/profile/orders",
                'returnUrl' => config('services.frontend_url') . "/thank-you",
                'invoice' => $invoice,
                'expiredAt' => now()->addMinutes(15)->timestamp
            ];

            // tạo link thanh toán với sdk
            $response = $this->payOs->createPaymentLink($data);

            // Trả về link thanh toán cho fe
            return ApiResponse::success('Link thanh toán được tạo thành công.', data: [
                'redirect_url' => $response['checkoutUrl']
            ]);

        } catch (\Exception $e) {
            logger('Log bug payos transaction', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            throw new ApiException('Có lỗi xảy ra trong quá trình tạo thanh toán!!');
        }
    }
}
