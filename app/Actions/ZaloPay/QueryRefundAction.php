<?php
namespace App\Actions\ZaloPay;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;

class QueryRefundAction {
    public static function run($params = [])
    {
        try {
            // Tạo rawHash
            $hashData = implode('|', [
                $params['app_id'],
                $params['m_refund_id'],
                $params['timestamp']
            ]);

            // Tạo mac
            $mac = hash_hmac('sha256', $hashData, $params['zp_key1']);

            $body = [
                'app_id' => (int) $params['app_id'],
                'm_refund_id' => $params['m_refund_id'],
                'timestamp' => $params['timestamp'],
                'mac' => $mac
            ];

            // gọi api query refund của ZaloPay
            $response = Http::asJson()->post(env('ZALO_PAY_REFUND_URL'), $body);

            return $response->json();
        }catch (\Exception $e) {
            logger('Query refund error', [
                'response' => $e
            ]);
            throw new ApiException('Có lỗi khi hoàn tiền, vui lòng thử lại!!');
        }
    }
}
