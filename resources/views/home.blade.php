<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homi - Trang chủ</title>

    <style>
        :root {
            --blue: #2f80ed;
            --blue-dark: #155fbd;
            --blue-light: #eaf4ff;
            --blue-soft: #f5fbff;
            --white: #ffffff;
            --text: #16324f;
            --muted: #6a7d95;
            --border: #d8eaff;
            --shadow: 0 18px 40px rgba(47, 128, 237, 0.14);
            --radius-lg: 28px;
            --radius-md: 18px;
            --radius-sm: 12px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: linear-gradient(180deg, #f2f9ff 0%, #ffffff 42%, #f7fbff 100%);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(216, 234, 255, 0.9);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 8px 0;
            font-size: 13px;
            color: var(--muted);
            border-bottom: 1px solid #eef6ff;
        }

        .top-left,
        .top-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .main-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 16px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 28px;
            font-weight: 900;
            color: var(--blue);
            letter-spacing: 0.4px;
        }

        .logo-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #6ec8ff 0%, var(--blue) 100%);
            color: var(--white);
            box-shadow: 0 10px 20px rgba(47, 128, 237, 0.25);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 22px;
            font-size: 15px;
            font-weight: 700;
            color: #25415f;
        }

        .nav-menu a {
            transition: 0.2s ease;
        }

        .nav-menu a:hover {
            color: var(--blue);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 999px;
            padding: 12px 20px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s ease;
            font-size: 14px;
        }

        .btn-primary {
            color: var(--white);
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
            box-shadow: 0 10px 22px rgba(47, 128, 237, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(47, 128, 237, 0.30);
        }

        .btn-outline {
            color: var(--blue);
            background: var(--white);
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background: var(--blue-light);
        }

        .logout-form {
            margin: 0;
        }

        .hero {
            position: relative;
            min-height: 720px;
            padding-top: 135px;
            overflow: hidden;
            background:
                linear-gradient(120deg, rgba(11, 79, 154, 0.80), rgba(47, 128, 237, 0.45)),
                url("https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1800&q=80");
            background-size: cover;
            background-position: center;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: auto 0 0 0;
            height: 140px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, #f2f9ff 100%);
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            padding: 90px 0 180px;
        }

        .hero-content {
            max-width: 720px;
            color: var(--white);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.25);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        .hero h1 {
            margin: 0 0 20px;
            font-size: clamp(42px, 6vw, 72px);
            line-height: 1.05;
            letter-spacing: -1.8px;
        }

        .hero p {
            margin: 0;
            max-width: 620px;
            font-size: 18px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.92);
        }

        .booking-box {
            position: relative;
            z-index: 5;
            margin-top: -110px;
        }

        .booking-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 24px;
        }

        .booking-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 18px;
        }

        .booking-title h2 {
            margin: 0;
            font-size: 24px;
        }

        .booking-title p {
            margin: 6px 0 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .booking-form {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 0.8fr;
            gap: 14px;
            align-items: end;
        }

        .form-group {
            display: grid;
            gap: 8px;
        }

        label {
            color: #2c4a68;
            font-size: 13px;
            font-weight: 800;
        }

        input,
        select {
            width: 100%;
            height: 52px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: #fbfdff;
            color: var(--text);
            padding: 0 15px;
            font-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        input:focus,
        select:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(47, 128, 237, 0.12);
            background: var(--white);
        }

        .search-btn {
            height: 52px;
            border-radius: 16px;
        }

        .section {
            padding: 78px 0;
        }

        .section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 30px;
        }

        .kicker {
            display: inline-block;
            color: var(--blue);
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 10px;
        }

        .section-head h2 {
            margin: 0;
            font-size: clamp(30px, 4vw, 44px);
            line-height: 1.14;
            letter-spacing: -0.8px;
        }

        .section-head p {
            margin: 0;
            max-width: 500px;
            color: var(--muted);
            line-height: 1.75;
        }

        .destination-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .tab {
            padding: 10px 16px;
            border-radius: 999px;
            background: var(--white);
            border: 1px solid var(--border);
            color: #2a4a68;
            font-weight: 800;
            font-size: 14px;
            box-shadow: 0 8px 18px rgba(47, 128, 237, 0.06);
        }

        .tab.active {
            background: var(--blue);
            color: var(--white);
            border-color: var(--blue);
        }

        .hotel-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .hotel-card {
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 12px 28px rgba(47, 128, 237, 0.08);
            transition: 0.22s ease;
        }

        .hotel-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 34px rgba(47, 128, 237, 0.14);
        }

        .hotel-image {
            position: relative;
            height: 190px;
            background:
                linear-gradient(135deg, rgba(47, 128, 237, 0.25), rgba(255, 255, 255, 0.1)),
                url("https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=900&q=80");
            background-size: cover;
            background-position: center;
        }

        .hotel-card:nth-child(2n) .hotel-image {
            background:
                linear-gradient(135deg, rgba(47, 128, 237, 0.25), rgba(255, 255, 255, 0.1)),
                url("https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=900&q=80");
            background-size: cover;
            background-position: center;
        }

        .hotel-card:nth-child(3n) .hotel-image {
            background:
                linear-gradient(135deg, rgba(47, 128, 237, 0.25), rgba(255, 255, 255, 0.1)),
                url("https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=900&q=80");
            background-size: cover;
            background-position: center;
        }

        .hotel-stars {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(255, 255, 255, 0.92);
            color: #f59e0b;
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 13px;
        }

        .hotel-body {
            padding: 18px;
        }

        .hotel-city {
            color: var(--blue);
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 8px;
        }

        .hotel-name {
            margin: 0 0 10px;
            min-height: 48px;
            font-size: 19px;
            line-height: 1.25;
        }

        .hotel-address {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
            min-height: 44px;
            margin-bottom: 14px;
        }

        .hotel-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid #eef6ff;
        }

        .price {
            color: var(--blue-dark);
            font-weight: 900;
            font-size: 16px;
        }

        .price small {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .mini-btn {
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--blue-light);
            color: var(--blue);
            font-size: 13px;
            font-weight: 900;
        }

        .feature-section {
            background: linear-gradient(180deg, #ffffff 0%, #eef8ff 100%);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .feature-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 26px;
            box-shadow: 0 10px 26px rgba(47, 128, 237, 0.07);
        }

        .feature-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: var(--blue-light);
            color: var(--blue);
            font-size: 24px;
            margin-bottom: 18px;
        }

        .feature-card h3 {
            margin: 0 0 10px;
            font-size: 21px;
        }

        .feature-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.75;
        }

        .footer {
            padding: 38px 0;
            color: var(--muted);
            background: var(--white);
            border-top: 1px solid var(--border);
            text-align: center;
        }

        @media (max-width: 1100px) {
            .booking-form {
                grid-template-columns: 1fr 1fr;
            }

            .hotel-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .nav-menu {
                display: none;
            }
        }

        @media (max-width: 720px) {
            .top-bar {
                display: none;
            }

            .main-nav {
                padding: 12px 0;
            }

            .nav-actions {
                gap: 8px;
            }

            .btn {
                padding: 10px 14px;
            }

            .hero {
                padding-top: 80px;
                min-height: 640px;
            }

            .hero-inner {
                padding: 70px 0 150px;
            }

            .booking-form {
                grid-template-columns: 1fr;
            }

            .booking-title {
                flex-direction: column;
            }

            .section-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .hotel-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="top-bar">
                <div class="top-left">
                    <span>Hotline: 1900 0000</span>
                    <span>Email: support@homi.test</span>
                </div>

                <div class="top-right">
                    <span>Xin chào, {{ auth()->user()->name }}</span>
                    <span>Vai trò: {{ auth()->user()->role }}</span>
                </div>
            </div>

            <nav class="main-nav">
                <a href="{{ route('home') }}" class="logo">
                    <span class="logo-mark">H</span>
                    <span>Homi</span>
                </a>

                <div class="nav-menu">
                    <a href="#hotels">Khách sạn</a>
                    <a href="#destinations">Điểm đến</a>
                    <a href="#services">Dịch vụ</a>
                    <a href="#offers">Ưu đãi</a>

                    @if (in_array(auth()->user()->role, ['admin', 'staff']))
                        <a href="{{ route('admin.hotels.index') }}">Quản lý khách sạn</a>
                        <a href="{{ route('admin.database') }}">Database</a>
                    @endif
                </div>

                <div class="nav-actions">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline">Dashboard</a>

                    <form method="POST" action="{{ route('logout') }}" class="logout-form">
                        @csrf
                        <button type="submit" class="btn btn-primary">Đăng xuất</button>
                    </form>
                </div>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-content">
                <div class="hero-badge">Homi Hotel Booking</div>
                <h1>Tìm khách sạn đẹp cho chuyến đi của bạn</h1>
                <p>
                    Khám phá khách sạn, xem loại phòng, kiểm tra thời gian lưu trú và chuẩn bị đặt phòng trực tuyến trên hệ thống Homi.
                </p>
            </div>
        </div>
    </section>

    <section class="booking-box">
        <div class="container">
            <div class="booking-card">
                <div class="booking-title">
                    <div>
                        <h2>Đặt phòng nhanh</h2>
                        <p>Chọn điểm đến, thời gian lưu trú và tìm khách sạn phù hợp.</p>
                    </div>

                    <a href="#hotels" class="btn btn-outline">Xem khách sạn</a>
                </div>

                <form method="GET" action="#hotels" class="booking-form">
                    <div class="form-group">
                        <label>Điểm đến hoặc khách sạn</label>
                        <select name="city">
                            <option value="">Tất cả điểm đến</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->city }}">
                                    {{ $city->city }} ({{ $city->total }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ngày nhận phòng</label>
                        <input type="date" name="check_in">
                    </div>

                    <div class="form-group">
                        <label>Ngày trả phòng</label>
                        <input type="date" name="check_out">
                    </div>

                    <button type="submit" class="btn btn-primary search-btn">Tìm phòng</button>
                </form>
            </div>
        </div>
    </section>

    <section class="section" id="destinations">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="kicker">Điểm đến nổi bật</span>
                    <h2>Khám phá các thành phố có khách sạn Homi</h2>
                </div>
                <p>Hiển thị dữ liệu trực tiếp từ bảng hotels. Có dữ liệu là hiện, không có thì khỏi giả vờ như hệ thống đã thành đế chế du lịch.</p>
            </div>

            <div class="destination-tabs">
                <span class="tab active">Tất cả</span>
                @foreach ($cities as $city)
                    <span class="tab">{{ $city->city }} ({{ $city->total }})</span>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section" id="hotels" style="padding-top: 0;">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="kicker">Khách sạn</span>
                    <h2>Danh sách khách sạn</h2>
                </div>
                <p>Các khách sạn đang hoạt động trong database, hiển thị theo dạng card hiện đại.</p>
            </div>

            <div class="hotel-grid">
                @forelse ($hotels as $hotel)
                    <article class="hotel-card">
                        <div class="hotel-image">
                            <div class="hotel-stars">{{ $hotel->star_rating }} sao</div>
                        </div>

                        <div class="hotel-body">
                            <div class="hotel-city">{{ $hotel->city }} · {{ $hotel->district }}</div>
                            <h3 class="hotel-name">{{ $hotel->name }}</h3>

                            <div class="hotel-address">
                                {{ $hotel->address }}
                            </div>

                            <div class="hotel-bottom">
                                <div class="price">
                                    @if ($hotel->min_price)
                                        {{ number_format($hotel->min_price, 0, ',', '.') }}đ
                                        <small>/ đêm từ</small>
                                    @else
                                        Đang cập nhật
                                        <small>giá phòng</small>
                                    @endif
                                </div>

                                <a href="#" class="mini-btn">Xem phòng</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <p>Chưa có khách sạn nào trong database.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section feature-section" id="services">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="kicker">Dịch vụ</span>
                    <h2>Trải nghiệm lưu trú dễ quản lý hơn</h2>
                </div>
                <p>Phần này dùng để làm đẹp trang chủ và giới thiệu các chức năng chính của đồ án.</p>
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">🏨</div>
                    <h3>Khách sạn rõ ràng</h3>
                    <p>Hiển thị tên khách sạn, địa chỉ, thành phố, hạng sao và giá phòng thấp nhất.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛏️</div>
                    <h3>Loại phòng đa dạng</h3>
                    <p>Dữ liệu phòng lấy từ bảng room_types, phục vụ bước chọn phòng và đặt phòng sau này.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Phân quyền an toàn</h3>
                    <p>Customer xem trang chủ, admin và staff có thêm quyền truy cập database cơ bản.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="offers">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="kicker">Ưu đãi</span>
                    <h2>Ưu đãi Homi sắp ra mắt</h2>
                </div>
                <p>Module ưu đãi có thể làm sau. Hiện tại nên ưu tiên khách sạn, phòng, kiểm tra phòng trống và đặt phòng. Đúng trọng tâm, đỡ lạc vào mê cung trang trí.</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            Homi Hotel Booking · Website đặt phòng khách sạn · Laravel Project
        </div>
    </footer>
</body>
</html>