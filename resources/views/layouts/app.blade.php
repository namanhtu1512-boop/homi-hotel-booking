<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Homi')</title>
    <style>
        :root {
            --primary: #1e5eff;
            --primary-dark: #1147c9;
            --primary-soft: #edf4ff;
            --primary-soft-2: #dbe9ff;
            --white: #ffffff;
            --text: #1f2a44;
            --muted: #6c7a96;
            --border: #d6e4ff;
            --bg: #f4f8ff;
            --success: #1a9b5b;
            --danger: #d93025;
            --shadow: 0 18px 40px rgba(17, 71, 201, 0.10);
            --shadow-light: 0 10px 24px rgba(17, 71, 201, 0.08);
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 12px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #f2f7ff 0%, #f8fbff 100%);
            color: var(--text);
        }

        a {
            text-decoration: none;
            color: var(--primary);
        }

        .container {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .top-banner {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0b46c4 0%, #1e5eff 48%, #6aa7ff 100%);
            color: var(--white);
            padding: 26px 0 88px;
        }

        .top-banner::before,
        .top-banner::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.10);
            pointer-events: none;
        }

        .top-banner::before {
            width: 320px;
            height: 320px;
            top: -100px;
            right: -90px;
        }

        .top-banner::after {
            width: 220px;
            height: 220px;
            left: -60px;
            bottom: -80px;
        }

        .navbar {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 34px;
        }

        .brand {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.4px;
            color: var(--white);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.92);
            font-weight: 600;
            padding: 10px 14px;
            border-radius: 999px;
            transition: 0.2s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.12);
            color: var(--white);
        }

        .banner-content {
            position: relative;
            z-index: 2;
            max-width: 760px;
        }

        .banner-tag {
            display: inline-block;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            margin-bottom: 18px;
        }

        .banner-content h1 {
            margin: 0 0 14px;
            font-size: clamp(30px, 4vw, 46px);
            line-height: 1.15;
        }

        .banner-content p {
            margin: 0;
            max-width: 720px;
            font-size: 16px;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.92);
        }

        .page-shell {
            margin-top: -50px;
            padding-bottom: 36px;
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 28px;
        }

        .card+.card {
            margin-top: 22px;
        }

        .section-kicker {
            display: inline-block;
            margin-bottom: 8px;
            color: var(--primary);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 28px;
            line-height: 1.2;
        }

        .section-desc {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            padding: 12px 18px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            box-shadow: var(--shadow-light);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            opacity: 0.97;
        }

        .btn-outline {
            background: var(--white);
            color: var(--primary);
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background: var(--primary-soft);
        }

        .btn-light {
            background: rgba(255, 255, 255, 0.16);
            color: var(--white);
            border: 1px solid rgba(255, 255, 255, 0.22);
        }

        .btn-light:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .btn-block {
            width: 100%;
        }

        .form-grid {
            display: grid;
            gap: 18px;
        }

        .form-group {
            display: grid;
            gap: 8px;
        }

        label {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
        }

        input {
            width: 100%;
            height: 48px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fbfdff;
            padding: 0 14px;
            font-size: 15px;
            color: var(--text);
            outline: none;
            transition: 0.2s ease;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(30, 94, 255, 0.10);
            background: var(--white);
        }

        select,
        textarea {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fbfdff;
            padding: 12px 14px;
            font-size: 15px;
            font-family: inherit;
            color: var(--text);
            outline: none;
            transition: 0.2s ease;
        }

        select {
            height: 48px;
        }

        textarea {
            resize: vertical;
        }

        select:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(30, 94, 255, 0.10);
            background: var(--white);
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
        }

        .checkbox-item input {
            width: auto;
            height: auto;
        }

        .badge-orange {
            background: #fff3e0;
            color: #b15c00;
        }

        .badge-red {
            background: #fdeceb;
            color: var(--danger);
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 13px;
            border-radius: 10px;
        }

        .btn-danger {
            background: #fdeceb;
            color: var(--danger);
            border: 1px solid #f6c8c3;
        }

        .btn-danger:hover {
            background: #fbdedb;
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 22px;
        }

        .filter-bar input,
        .filter-bar select {
            width: auto;
            min-width: 180px;
        }

        .alert-success {
            background: #eafaf1;
            border: 1px solid #bfeed4;
            color: var(--success);
        }

        .alert {
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.6;
        }

        .alert-danger {
            background: #fff1f0;
            border: 1px solid #ffd1cc;
            color: var(--danger);
        }

        .auth-layout {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
            align-items: stretch;
        }

        .auth-card {
            min-height: 100%;
        }

        .auth-side {
            min-height: 100%;
            background: linear-gradient(180deg, #f7fbff 0%, #edf4ff 100%);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 28px;
        }

        .auth-features {
            margin-top: 18px;
            display: grid;
            gap: 14px;
        }

        .feature-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px 18px;
        }

        .feature-box h4 {
            margin: 0 0 8px;
            font-size: 16px;
        }

        .feature-box p {
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
            font-size: 14px;
        }

        .auth-footer {
            margin-top: 16px;
            color: var(--muted);
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px;
            box-shadow: var(--shadow-light);
        }

        .stat-label {
            margin-bottom: 8px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
        }

        .stat-note {
            margin-top: 6px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 22px;
        }

        .info-list {
            display: grid;
            gap: 16px;
            margin-top: 16px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #f9fbff;
            border: 1px solid var(--border);
        }

        .info-item .label {
            color: var(--muted);
            font-weight: 600;
        }

        .info-item .value {
            color: var(--text);
            font-weight: 700;
            text-align: right;
            word-break: break-word;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-blue {
            background: #e8f0ff;
            color: #1850d8;
        }

        .badge-green {
            background: #e8f8ef;
            color: #1a8d55;
        }

        .quick-actions {
            margin-top: 16px;
            display: grid;
            gap: 12px;
        }

        .table-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .table-count {
            min-width: 88px;
            text-align: center;
            background: var(--primary-soft);
            color: var(--primary);
            border-radius: 14px;
            padding: 12px 14px;
            font-weight: 800;
        }

        .db-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .db-summary-card {
            background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
        }

        .db-summary-card .name {
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: capitalize;
        }

        .db-summary-card .value {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
        }

        .table-wrapper {
            width: 100%;
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
            background: var(--white);
        }

        thead th {
            background: #edf4ff;
            color: #23408b;
            font-size: 13px;
            font-weight: 800;
            text-align: left;
            padding: 14px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody td {
            padding: 13px 14px;
            border-bottom: 1px solid #eef3ff;
            font-size: 14px;
            color: var(--text);
            vertical-align: top;
        }

        tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        tbody tr:hover {
            background: #f2f7ff;
        }

        .empty-box {
            padding: 18px;
            border-radius: 14px;
            background: #f8fbff;
            border: 1px dashed var(--border);
            color: var(--muted);
        }

        .page-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .logout-form {
            margin: 0;
        }

        @media (max-width: 1024px) {

            .auth-layout,
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid,
            .db-summary {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .container {
                width: min(100% - 20px, 100%);
            }

            .top-banner {
                padding: 18px 0 76px;
            }

            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .brand {
                font-size: 24px;
            }

            .card,
            .auth-side {
                padding: 20px;
                border-radius: 18px;
            }

            .stats-grid,
            .db-summary {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 24px;
            }

            .banner-content h1 {
                font-size: 30px;
            }

            .info-item {
                flex-direction: column;
            }

            .info-item .value {
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <header class="top-banner">
        <div class="container">
            <div class="navbar">
                <div class="brand">Homi</div>

                <div class="nav-right">
                    @if (auth()->check())
                        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>

                        @if (in_array(auth()->user()->role, ['admin', 'staff']))
                            <a href="{{ route('admin.dashboard') }}" class="nav-link">Trang quản trị</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="logout-form">
                            @csrf
                            <button type="submit" class="btn btn-light">Đăng xuất</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="nav-link">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="btn btn-light">Đăng ký</a>
                    @endif
                </div>
            </div>

            <div class="banner-content">
                <div class="banner-tag">@yield('banner_tag', 'Homi Hotel Booking')</div>
                <h1>@yield('banner_title', 'Hệ thống quản lý đặt phòng Homi')</h1>
                <p>@yield('banner_subtitle', 'Giao diện hiện đại, rõ ràng, dễ thao tác để quản lý tài khoản, khách sạn, loại phòng và dữ liệu đặt phòng.') </p>
            </div>
        </div>
    </header>

    <main class="page-shell">
        <div class="container">
            @yield('content')
        </div>
    </main>
</body>

</html>
