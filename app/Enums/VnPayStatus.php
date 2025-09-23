<?php

namespace App\Enums;

enum VnPayStatus: string
{
    // ===== IPN/Return chỉ dùng riêng =====
    case SUCCESS = '00';
    case FRAUD_SUSPECTED = '07';
    case NO_INTERNET_BANKING = '09';
    case WRONG_AUTH_INFO = '10';
    case TIMEOUT = '11';
    case ACCOUNT_LOCKED = '12';
    case WRONG_OTP = '13';
    case CUSTOMER_CANCEL = '24';
    case INSUFFICIENT_FUNDS = '51';
    case LIMIT_EXCEEDED = '65';
    case BANK_MAINTENANCE = '75';
    case WRONG_PAYMENT_PASSWORD = '79';

    // ===== Các mã có thể trùng ở QueryDR / Refund =====
    case CODE_02 = '02';
    case CODE_03 = '03';
    case CODE_04 = '04';
    case CODE_91 = '91';
    case CODE_93 = '93';
    case CODE_94 = '94';
    case CODE_95 = '95';
    case CODE_97 = '97';
    case CODE_98 = '98';
    case CODE_99 = '99';

    public static function description(string $code, ?string $command = null): string
    {
        return match ([$code, $command]) {
            // QueryDR
            ['02', 'querydr'] => 'Merchant không hợp lệ',
            ['03', 'querydr'] => 'Dữ liệu sai định dạng',
            ['91', 'querydr'] => 'Không tìm thấy giao dịch yêu cầu',
            ['94', 'querydr'] => 'Yêu cầu bị trùng lặp',
            ['97', 'querydr'] => 'Chữ ký không hợp lệ',
            ['99', 'querydr'] => 'Các lỗi khác',

            // Refund
            ['02', 'refund']  => 'Tổng số tiền hoàn trả lớn hơn số tiền gốc',
            ['03', 'refund']  => 'Dữ liệu sai định dạng',
            ['04', 'refund']  => 'Không cho phép hoàn trả toàn phần sau khi hoàn trả một phần',
            ['13', 'refund']  => 'Chỉ cho phép hoàn trả một phần',
            ['91', 'refund']  => 'Không tìm thấy giao dịch yêu cầu hoàn trả',
            ['93', 'refund']  => 'Số tiền hoàn trả không hợp lệ',
            ['94', 'refund']  => 'Yêu cầu bị trùng lặp',
            ['95', 'refund']  => 'VNPAY từ chối xử lý yêu cầu',
            ['97', 'refund']  => 'Chữ ký không hợp lệ',
            ['98', 'refund']  => 'Timeout Exception',
            ['99', 'refund']  => 'Các lỗi khác',

            // IPN/Return (command = null)
            ['00', null] => 'Giao dịch thành công',
            ['02', null] => 'Đơn hàng đã được xác nhận',
            ['04', null] => 'số tiền không hợp lệ',
            ['07', null] => 'Trừ tiền thành công nhưng nghi ngờ gian lận',
            ['09', null] => 'Chưa đăng ký Internet Banking',
            ['10', null] => 'Xác thực không đúng quá 3 lần',
            ['11', null] => 'Hết hạn chờ thanh toán',
            ['12', null] => 'Tài khoản bị khóa',
            ['13', null] => 'Sai OTP',
            ['24', null] => 'Khách hàng hủy giao dịch',
            ['51', null] => 'Không đủ số dư',
            ['65', null] => 'Vượt hạn mức giao dịch trong ngày',
            ['75', null] => 'Ngân hàng đang bảo trì',
            ['79', null] => 'Sai mật khẩu thanh toán quá số lần quy định',
            ['97', null] => 'Chữ ký không hợp lệ',
            ['99', null] => 'Các lỗi khác',

            default => 'Không xác định'
        };
    }
}
