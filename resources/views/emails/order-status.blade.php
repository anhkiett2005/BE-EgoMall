<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Thông báo đơn hàng</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;">
        <h2 style="text-align: center; color: #2e7d32;">
            @switch($status)
                @case('ordered') 🛒 Đặt hàng thành công! @break
                @case('confirmed') ✅ Đơn hàng đã được xác nhận! @break
                @case('delivered') 📦 Đơn hàng đã giao thành công! @break
                @default 📋 Cập nhật đơn hàng
            @endswitch
        </h2>

        <p>Xin chào {{ $order->shipping_name }},</p>

        @if ($status === 'ordered')
            <p>Cảm ơn bạn đã mua sắm tại <strong>EgoMall</strong>. Đơn hàng của bạn đã được tạo thành công.</p>
        @elseif ($status === 'confirmed')
            <p>Đơn hàng của bạn đã được xác nhận và đang được chuẩn bị để giao đến bạn.</p>
        @elseif ($status === 'delivered')
            <p>Chúng tôi xin thông báo rằng đơn hàng của bạn đã được giao thành công.</p>
        @endif

        <p><strong>Mã đơn hàng:</strong> {{ $order->unique_id }}</p>
        <p><strong>Ngày đặt:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
        <p><strong>Phương thức thanh toán:</strong> {{ strtoupper($order->payment_method) }}</p>

        @php
            $displayStatus = [
                'ordered' => 'Chờ xác nhận',
                'confirmed' => 'Đã xác nhận',
                'delivered' => 'Hoàn tất',
            ][$status] ?? ucfirst($status);
        @endphp

        <p><strong>Trạng thái hiện tại:</strong> {{ $displayStatus }}</p>

        <hr>

        <p><strong>Tổng tiền:</strong> {{ number_format($order->total_price, 0, ',', '.') }}₫</p>
        <p><strong>Địa chỉ giao hàng:</strong> {{ $order->shipping_address }}</p>

        <p style="margin-top: 20px;">
            👉 Bạn có thể <a href="https://egomall.com.vn/orders" style="color: #2e7d32; font-weight: bold;">xem chi tiết đơn hàng tại đây</a>.
        </p>

        <p style="margin-top: 30px;">Trân trọng,<br>Đội ngũ <strong>EgoMall</strong></p>
    </div>
</body>
</html>
