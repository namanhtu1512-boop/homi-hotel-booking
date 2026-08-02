<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập nhân viên · Homi</title>
    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --sidebar-bg: #042f2e;
            --white: #ffffff;
            --text: #1f2a44;
            --muted: #6c7a96;
            --border: #ccf0ea;
            --danger: #d93025;
            --radius-lg: 20px;
            --radius-md: 14px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--sidebar-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrapper {
            display: flex;
            width: min(900px, calc(100% - 32px));
            min-height: 520px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.45);
        }

        .login-side {
            width: 340px;
            flex-shrink: 0;
            background: linear-gradient(160deg, #0f4c47 0%, #042f2e 100%);
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            color: #b7e4dd;
        }

        .brand {
            color: var(--white);
            font-size: 26px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 36px;
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--primary);
            color: var(--white);
            font-size: 18px;
            font-weight: 900;
        }

        .side-kicker {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #5eead4;
            margin-bottom: 10px;
        }

        .side-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--white);
            line-height: 1.3;
            margin-bottom: 14px;
        }

        .side-desc {
            font-size: 14px;
            line-height: 1.75;
            color: #8fc9c0;
        }

        .side-features {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .side-feature {
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.07);
        }

        .side-feature h4 {
            color: var(--white);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .side-feature p {
            font-size: 12px;
            color: #8fc9c0;
            line-height: 1.5;
        }

        .login-form-area {
            flex: 1;
            background: var(--white);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-kicker {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .form-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 6px;
        }

        .form-desc {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 28px;
            line-height: 1.6;
        }

        .alert-danger {
            background: #fff1f0;
            border: 1px solid #ffd1cc;
            color: var(--danger);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .form-grid { display: grid; gap: 16px; }

        .form-group { display: grid; gap: 6px; }

        label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
        }

        input {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fbfdff;
            padding: 11px 14px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text);
            outline: none;
            transition: 0.2s ease;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
            background: var(--white);
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s ease;
            font-family: inherit;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
        }

        .form-footer {
            margin-top: 20px;
            font-size: 13px;
            color: var(--muted);
            text-align: center;
        }

        .form-footer a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        @media (max-width: 680px) {
            .login-side { display: none; }
            .login-form-area { padding: 32px 24px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-side">
            <div class="brand">
                <div class="brand-mark">H</div>
                Homi
            </div>

            <div class="side-kicker">Khu vực nhân viên</div>
            <div class="side-title">Cổng đăng nhập dành cho Nhân viên</div>
            <p class="side-desc">Trang này chỉ dành cho tài khoản nhân viên vận hành khách sạn.</p>

            <div class="side-features">
                <div class="side-feature">
                    <h4>Đặt phòng & Check-in/out</h4>
                    <p>Quản lý đơn đặt phòng, nhận/trả phòng cho khách.</p>
                </div>
                <div class="side-feature">
                    <h4>Thanh toán</h4>
                    <p>Cập nhật trạng thái thanh toán các đơn.</p>
                </div>
                <div class="side-feature">
                    <h4>Yêu cầu của khách</h4>
                    <p>Xử lý đổi phòng, nhận sớm/trả muộn.</p>
                </div>
            </div>
        </div>

        <div class="login-form-area">
            <div class="form-kicker">Xác thực</div>
            <div class="form-title">Đăng nhập nhân viên</div>
            <p class="form-desc">Nhập thông tin tài khoản nhân viên để tiếp tục.</p>

            @if ($errors->any())
                <div class="alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('staff.login.post') }}" class="form-grid">
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        placeholder="staff@homi.test" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input id="password" type="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>

                <button type="submit" class="btn-submit">Đăng nhập</button>
            </form>

            <div class="form-footer">
                Là quản trị viên? <a href="{{ route('admin.login') }}">Đăng nhập tại đây</a>
            </div>
        </div>
    </div>
</body>
</html>
