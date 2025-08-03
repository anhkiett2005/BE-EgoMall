<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Thông báo khuyến mãi</title>
</head>
<body>
    <h2>🎉 Chương trình khuyến mãi mới tại EgoMall!</h2>

    <p><strong>Tên chương trình:</strong> {{ $promotion->name }}</p>
    <p><strong>Thời gian:</strong> từ {{ \Carbon\Carbon::parse($promotion->start_date)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($promotion->end_date)->format('d/m/Y') }}</p>

    @if ($promotion->description)
        <p><strong>Mô tả:</strong> {{ $promotion->description }}</p>
    @endif

    @if (in_array($promotion->promotion_type, ['percentage', 'fixed_amount']))
        <p><strong>Giảm giá:</strong>
            @if ($promotion->discount_type === 'percentage')
                {{ $promotion->discount_value }}%
            @else
                {{ number_format($promotion->discount_value, 0) }}đ
            @endif
        </p>
    @elseif ($promotion->promotion_type === 'buy_get')
        <p><strong>Khuyến mãi:</strong> Mua {{ $promotion->buy_quantity }} tặng {{ $promotion->get_quantity }}</p>
    @endif

    <p>👉 Truy cập website để xem chi tiết và mua sắm ngay hôm nay!</p>

    <p>Trân trọng,<br>Đội ngũ EgoMall</p>
</body>
</html>
