<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông báo khuyến mãi</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #fff0f5;
            margin: 0;
            padding: 0;
        }
        .container {
            background: #ffffff;
            max-width: 560px;
            margin: 40px auto;
            padding: 30px 28px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(233, 30, 99, 0.1);
            border: 1px solid #f8bbd0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header .icon {
            font-size: 2.6rem;
            display: block;
            margin-bottom: 8px;
        }
        .header h2 {
            color: #e91e63;
            font-size: 1.8rem;
            margin: 0;
            font-weight: bold;
        }
        .info {
            font-size: 1.05rem;
            line-height: 1.7;
            color: #444;
        }
        .info p {
            margin: 12px 0;
        }
        .highlight {
            color: #e91e63;
            font-weight: bold;
        }
        .button-wrap {
            text-align: center;
            margin-top: 24px;
        }
        .button {
            display: inline-block;
            background: #e91e63;
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 1.05rem;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(233,30,99,0.15);
            transition: background 0.2s ease-in-out;
        }
        .button:hover {
            background: #d81b60;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.95rem;
            color: #777;
            border-top: 1px dashed #f48fb1;
            padding-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="icon">🎉</span>
            <h2>Chương trình khuyến mãi mới tại EgoMall!</h2>
        </div>
        <div class="info">
            <p><strong>Tên chương trình:</strong> <span class="highlight">{{ $promotion->name }}</span></p>
            <p><strong>Thời gian:</strong>
                từ <span class="highlight">{{ \Carbon\Carbon::parse($promotion->start_date)->format('d/m/Y') }}</span>
                đến <span class="highlight">{{ \Carbon\Carbon::parse($promotion->end_date)->format('d/m/Y') }}</span>
            </p>
            @if ($promotion->description)
                <p><strong>Mô tả:</strong> {{ $promotion->description }}</p>
            @endif
            @if (in_array($promotion->promotion_type, ['percentage', 'fixed_amount']))
                <p><strong>Giảm giá:</strong>
                    <span class="highlight">
                        @if ($promotion->discount_type === 'percentage')
                            {{ $promotion->discount_value }}%
                        @else
                            {{ number_format($promotion->discount_value, 0) }}đ
                        @endif
                    </span>
                </p>
            @elseif ($promotion->promotion_type === 'buy_get')
                <p><strong>Khuyến mãi:</strong>
                    Mua <span class="highlight">{{ $promotion->buy_quantity }}</span>
                    tặng <span class="highlight">{{ $promotion->get_quantity }}</span>
                </p>
            @endif
        </div>
        <div class="button-wrap">
            <a href="{{ config('app.url') }}" class="button">👉 Xem chi tiết & Mua ngay</a>
        </div>
        <div class="footer">
            Trân trọng,<br>
            <strong style="color: #e91e63;">Đội ngũ EgoMall</strong>
        </div>
    </div>
</body>
</html>
