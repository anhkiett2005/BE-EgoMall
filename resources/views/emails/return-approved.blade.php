<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Yêu cầu hoàn trả đã được chấp nhận</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,.1);">

        <h2 style="color:#2c3e50;">Xin chào {{ $order->shipping_name }},</h2>

        <p>
            Yêu cầu <strong>hoàn trả</strong> cho đơn hàng
            <strong>#{{ $order->unique_id }}</strong> của bạn đã được
            <span style="color:green;font-weight:bold;">CHẤP NHẬN</span>.
        </p>

        <h3 style="margin-top:20px; color:#2c3e50;">Thông tin đơn hàng</h3>
        <ul style="padding-left:18px;">
            <li><b>Mã đơn:</b> {{ $order->unique_id }}</li>
            <li><b>Tổng tiền:</b> {{ number_format($order->total_price) }}đ</li>
            <li><b>Phương thức thanh toán:</b> {{ $order->payment_method }}</li>
            <li><b>Ngày giao hàng:</b> {{ optional($order->delivered_at)->format('d/m/Y H:i') }}</li>
        </ul>

        <h3 style="margin-top:20px; color:#2c3e50;">Quy trình đổi trả</h3>
        <ol style="padding-left:18px;">
            <li>Đóng gói sản phẩm cẩn thận, giữ nguyên hộp, tem, nhãn và phụ kiện (nếu có).</li>
            <li>Đảm bảo sản phẩm chưa qua sử dụng hoặc còn trong tình trạng nguyên vẹn.</li>
            <li>Đính kèm hóa đơn mua hàng (hoặc ảnh chụp hóa đơn điện tử).</li>
            <li>Gửi sản phẩm về địa chỉ được nhân viên CSKH cung cấp qua điện thoại hoặc email.</li>
        </ol>

        <h3 style="margin-top:20px; color:#2c3e50;">Lưu ý quan trọng</h3>
        <ul style="padding-left:18px;">
            <li>Thời hạn đổi trả: trong vòng <strong>7 ngày</strong> kể từ ngày giao hàng thành công.</li>
            <li>Chúng tôi chỉ chấp nhận hoàn trả với sản phẩm còn nguyên vẹn, chưa sử dụng.</li>
            <li>Phí vận chuyển hoàn trả: tuỳ theo chính sách hiện hành (liên hệ CSKH để biết thêm chi tiết).</li>
            <li>Sau khi kiểm tra sản phẩm, chúng tôi sẽ hoàn tiền về phương thức thanh toán ban đầu hoặc theo thoả thuận.</li>
        </ul>

        <p style="margin-top:20px;">
            Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ
            Email: <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
            hoặc gọi hotline: 0868802777 để được hỗ trợ sớm nhất.
        </p>

        <p style="margin-top:25px;">Trân trọng,<br>
        <strong>{{ config('mail.from.name') }}</strong></p>

    </div>
</body>
</html>
