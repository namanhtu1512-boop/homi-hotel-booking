<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $hotel->name }} - Homi</title>

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
            min-height: 640px;
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
            padding: 90px 0 160px;
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
            font-size: clamp(38px, 6vw, 64px);
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
            margin-top: -100px;
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
            grid-template-columns: 1fr 1fr 0.6fr;
            gap: 14px;
            align-items: end;
        }

        .booking-form--extended {
            grid-template-columns: 1fr 1fr 0.8fr 1fr 1fr auto;
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

        .amenity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }

        .amenity-tag {
            padding: 9px 16px;
            border-radius: 999px;
            background: var(--white);
            border: 1px solid var(--border);
            color: #2a4a68;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 8px 18px rgba(47, 128, 237, 0.06);
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .room-card {
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 12px 28px rgba(47, 128, 237, 0.08);
            transition: 0.22s ease;
        }

        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 34px rgba(47, 128, 237, 0.14);
        }

        .room-image {
            position: relative;
            height: 190px;
            background:
                linear-gradient(135deg, rgba(47, 128, 237, 0.25), rgba(255, 255, 255, 0.1)),
                url("https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=900&q=80");
            background-size: cover;
            background-position: center;
        }

        .room-card:nth-child(2n) .room-image {
            background:
                linear-gradient(135deg, rgba(47, 128, 237, 0.25), rgba(255, 255, 255, 0.1)),
                url("https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=900&q=80");
            background-size: cover;
            background-position: center;
        }

        .room-card:nth-child(3n) .room-image {
            background:
                linear-gradient(135deg, rgba(47, 128, 237, 0.25), rgba(255, 255, 255, 0.1)),
                url("https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=900&q=80");
            background-size: cover;
            background-position: center;
        }

        .room-capacity {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(255, 255, 255, 0.92);
            color: #155fbd;
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 13px;
        }

        .room-body {
            padding: 18px;
        }

        .room-bed {
            color: var(--blue);
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 8px;
        }

        .room-name {
            margin: 0 0 10px;
            min-height: 48px;
            font-size: 19px;
            line-height: 1.25;
        }

        .room-desc {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
            min-height: 44px;
            margin-bottom: 14px;
        }

        .room-bottom {
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

            .room-grid {
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
                min-height: 600px;
            }

            .hero-inner {
                padding: 70px 0 140px;
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

            .room-grid {
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

                @auth
                <div class="top-right">
                    <span>Xin chào, {{ auth()->user()->name }}</span>
                    <span>Vai trò: {{ auth()->user()->role }}</span>
                </div>
                @endauth
            </div>

            <nav class="main-nav">
                <a href="{{ route('home') }}" class="logo">
                    <span class="logo-mark">H</span>
                    <span>Homi</span>
                </a>

                <div class="nav-menu">
                    <a href="#rooms">Loại phòng</a>
                    <a href="#about">Giới thiệu</a>
                    <a href="#services">Dịch vụ</a>
                </div>

                <div class="nav-actions">
                    @auth
                        @php
                            $dashboardRoute = in_array(auth()->user()->role, ['admin', 'staff'])
                                ? route('admin.dashboard')
                                : route('customer.dashboard');
                        @endphp
                        <a href="{{ $dashboardRoute }}" class="btn btn-outline">Dashboard</a>

                        <form method="POST" action="{{ route('logout') }}" class="logout-form">
                            @csrf
                            <button type="submit" class="btn btn-primary">Đăng xuất</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="btn btn-primary">Đăng ký</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-content">
                <div class="hero-badge">{{ $hotel->star_rating ? $hotel->star_rating . ' sao' : 'Homi Hotel Booking' }}</div>
                <h1>{{ $hotel->name }}</h1>
                <p>{{ $hotel->description ?: 'Khám phá các loại phòng, kiểm tra thời gian lưu trú và chuẩn bị đặt phòng trực tuyến trên hệ thống Homi.' }}</p>
            </div>
        </div>
    </section>

    <section class="booking-box">
        <div class="container">
            <div class="booking-card">
                <div class="booking-title">
                    <div>
                        <h2>Tìm &amp; đặt phòng</h2>
                        <p>Chọn thời gian lưu trú và bộ lọc để xem các loại phòng phù hợp.</p>
                    </div>

                    <a href="#rooms" class="btn btn-outline">Xem loại phòng</a>
                </div>

                {{-- BE1/BE3 Tuần 7: form filter phòng — submit GET về cùng trang --}}
                <form method="GET" action="{{ route('home') }}" class="booking-form booking-form--extended">
                    <div class="form-group">
                        <label>Ngày nhận phòng</label>
                        <input type="date" name="check_in"
                               value="{{ $filters['check_in'] ?? '' }}"
                               min="{{ now()->toDateString() }}">
                    </div>

                    <div class="form-group">
                        <label>Ngày trả phòng</label>
                        <input type="date" name="check_out"
                               value="{{ $filters['check_out'] ?? '' }}"
                               min="{{ now()->addDay()->toDateString() }}">
                    </div>

                    <div class="form-group">
                        <label>Sức chứa (tối thiểu)</label>
                        <select name="capacity">
                            <option value="">Bất kỳ</option>
                            @foreach ([1,2,3,4] as $n)
                                <option value="{{ $n }}" @selected(($filters['capacity'] ?? '') == $n)>{{ $n }} khách</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Giá tối đa (đ)</label>
                        <input type="number" name="max_price" min="0" step="100000"
                               placeholder="VD: 2000000"
                               value="{{ $filters['max_price'] ?? '' }}">
                    </div>

                    <div class="form-group" style="grid-column: span 1;">
                        <label>Từ khoá</label>
                        <input type="text" name="keyword" maxlength="100"
                               placeholder="Standard, Deluxe..."
                               value="{{ $filters['keyword'] ?? '' }}">
                    </div>

                    <div style="display:flex;align-items:flex-end;gap:8px;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary search-btn">Tìm phòng</button>
                        @if (array_filter($filters))
                            <a href="{{ route('home') }}" class="btn btn-outline search-btn">Xoá bộ lọc</a>
                        @endif
                    </div>
                </form>

                @if ($errors->any())
                    <div style="margin-top:12px;color:#c0392b;font-size:13px;">
                        @foreach ($errors->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="section" id="about">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="kicker">Giới thiệu</span>
                    <h2>Vì sao chọn {{ $hotel->name }}</h2>
                </div>
                <p>{{ $hotel->address }}</p>
            </div>

            <div class="amenity-tags">
                @forelse ($hotel->amenities as $amenity)
                    <span class="amenity-tag">{{ $amenity->name }}</span>
                @empty
                    <span class="amenity-tag">Đang cập nhật tiện ích</span>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section" id="rooms" style="padding-top: 0;">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="kicker">Loại phòng</span>
                    <h2>Chọn loại phòng phù hợp</h2>
                </div>
                <p>
                    {{ $roomTypes->total() }} loại phòng đang mở bán tại {{ $hotel->name }}.
                    @if (array_filter($filters))
                        <span style="color:var(--blue)">(Đang lọc)</span>
                    @endif
                </p>
            </div>

            <div class="room-grid">
                @forelse ($roomTypes as $room)
                    <article class="room-card">
                        <div class="room-image">
                            <div class="room-capacity">{{ $room->capacity }} khách</div>
                        </div>

                        <div class="room-body">
                            <div class="room-bed">{{ $room->bed_type ?: 'Phòng nghỉ' }}</div>
                            <h3 class="room-name">{{ $room->name }}</h3>

                            <div class="room-desc">{{ $room->description }}</div>

                            <div class="room-bottom">
                                <div class="price">
                                    {{ number_format($room->price_per_night, 0, ',', '.') }}đ
                                    <small>/ đêm</small>
                                </div>

                                <span class="mini-btn">{{ $room->total_rooms }} phòng</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <p style="grid-column:1/-1;color:var(--muted);">
                        Không tìm thấy loại phòng phù hợp với bộ lọc.
                        <a href="{{ route('home') }}" style="color:var(--blue)">Xem tất cả</a>
                    </p>
                @endforelse
            </div>

            @if ($roomTypes->hasPages())
                <div style="margin-top:32px;display:flex;justify-content:center;">
                    {{ $roomTypes->links() }}
                </div>
            @endif
        </div>
    </section>

    <section class="section feature-section" id="services">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="kicker">Dịch vụ</span>
                    <h2>Trải nghiệm lưu trú dễ quản lý hơn</h2>
                </div>
                <p>Phần này giới thiệu các chức năng chính của hệ thống Homi.</p>
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">🏨</div>
                    <h3>Thông tin rõ ràng</h3>
                    <p>Hiển thị tên khách sạn, địa chỉ, hạng sao, tiện ích và chính sách lưu trú.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛏️</div>
                    <h3>Loại phòng đa dạng</h3>
                    <p>Dữ liệu phòng lấy trực tiếp từ bảng room_types, phục vụ bước chọn phòng và đặt phòng.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Phân quyền an toàn</h3>
                    <p>Customer xem trang chủ và đặt phòng, admin/staff có thêm quyền quản trị hệ thống.</p>
                </div>
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
