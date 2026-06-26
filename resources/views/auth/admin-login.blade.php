<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị · Homi</title>
    <style>
        :root {
            --primary: #1e5eff;
            --primary-dark: #1147c9;
            --primary-soft: #edf4ff;
            --sidebar-bg: #0b1530;
            --white: #ffffff;
            --text: #1f2a44;
            --muted: #6c7a96;
            --border: #d6e4ff;
            --danger: #d93025;
            --radius-lg: 20px;
            --radius-md: 14px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
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
            background: linear-gradient(160deg, #112060 0%, #0b1530 100%);
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            color: #b9c6e8;
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
            color: var(--primary);
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
            color: #8899bb;
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
            color: #8899bb;
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
            box-shadow: 0 0 0 3px rgba(30, 94, 255, 0.10);
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

            <div class="side-kicker">Khu vực quản trị</div>
            <div class="side-title">Cổng đăng nhập dành cho Admin & Staff</div>
            <p class="side-desc">Trang này chỉ dành cho tài khoản quản trị viên và nhân viên hệ thống.</p>

            <div class="side-features">
                <div class="side-feature">
                    <h4>Quản lý khách sạn</h4>
                    <p>Cập nhật thông tin, tiện ích và trạng thái hoạt động.</p>
                </div>
                <div class="side-feature">
                    <h4>Loại phòng & Đặt phòng</h4>
                    <p>Quản lý danh mục phòng, tồn kho và lịch sử booking.</p>
                </div>
                <div class="side-feature">
                    <h4>Quản lý người dùng</h4>
                    <p>Xem danh sách và kiểm soát trạng thái tài khoản.</p>
                </div>
            </div>
        </div>

        <div class="login-form-area">
            <div class="form-kicker">Xác thực</div>
            <div class="form-title">Đăng nhập quản trị</div>
            <p class="form-desc">Nhập thông tin tài khoản admin hoặc staff để tiếp tục.</p>

            @if ($errors->any())
                <div class="alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}" class="form-grid">
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        placeholder="admin@homi.test" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input id="password" type="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>

                <button type="submit" class="btn-submit">Đăng nhập</button>
            </form>
        </div>
    </div>
</body>
</html>
