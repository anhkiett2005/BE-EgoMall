<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thiết lập mật khẩu EgoMall</title>
</head>
<body>
    <h2>Xin chào {{ $userName }}!</h2>

    <p>Bạn vừa được tạo tài khoản với vai trò <strong>{{ $roleName }}</strong> trên hệ thống <strong>EgoMall</strong>.</p>

    <p><strong>Email đăng nhập:</strong> {{ $userEmail }}</p>

    <p>Để thiết lập mật khẩu của bạn, vui lòng nhấn vào nút bên dưới:</p>

    <p>
        <a href="https://admin.egomall.io.vn/auth/forgotpassword"
           style="display: inline-block; padding: 12px 24px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">
            Đặt lại mật khẩu
        </a>
    </p>

    <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email và không chia sẻ đường dẫn cho bất kỳ ai.</p>

    <p>Trân trọng,<br>Đội ngũ EgoMall</p>
</body>
</html>
