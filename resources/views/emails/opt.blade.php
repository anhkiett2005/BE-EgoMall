<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác Thực OTP | Egomall</title>
    <style>
        body {
            background-color: #fff0f5;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 560px;
            margin: 50px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(255, 105, 180, 0.2);
            border: 1px solid #ffe6f0;
        }

        .header {
            background: linear-gradient(135deg, #ffb6c1, #ff69b4);
            color: white;
            text-align: center;
            padding: 28px 24px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }

        .content {
            padding: 36px 32px;
            color: #333;
            line-height: 1.6;
        }

        .content h2 {
            font-size: 20px;
            margin-top: 0;
            color: #e91e63;
        }

        .otp-code {
            display: block;
            width: fit-content;
            margin: 32px auto;
            padding: 16px 32px;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 14px;
            color: #d81b60;
            background-color: #fff0f5;
            border: 2px dashed #f06292;
            border-radius: 12px;
        }

        .expire {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .footer {
            background-color: #fef0f5;
            color: #888;
            font-size: 13px;
            padding: 18px 24px;
            text-align: center;
            border-top: 1px solid #fce4ec;
        }

        .signature {
            margin-top: 28px;
            font-style: italic;
            font-size: 14px;
            text-align: right;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💖 Egomall 💖</h1>
        </div>
        <div class="content">
            <h2>Xin chào{{ isset($notifiable->name) ? ', ' . $notifiable->name : '' }}!</h2>
            <p>Chúng tôi đã nhận được yêu cầu xác thực tài khoản của bạn.</p>
            <p>Mã OTP của bạn là:</p>
            <div class="otp-code">{{ $otp }}</div>
            <p class="expire">Mã này sẽ hết hạn sau {{ $expiresInMinutes }} phút.</p>
            <p>Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email này.</p>

            <div class="signature">
                Trân trọng,<br>
                Đội ngũ Egomall
            </div>
        </div>
        <div class="footer">
            © {{ now()->year }} Egomall. Tất cả các quyền được bảo lưu.
        </div>
    </div>
</body>
</html>
