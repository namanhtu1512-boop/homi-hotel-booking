<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel · Homi')</title>
    <link rel="stylesheet" href="{{ asset('admin-assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/css/admin.css') }}">
    @stack('styles')
</head>
<body class="admin-body">

<div class="admin-shell">

    <aside class="admin-sidebar">
        <div class="admin-sidebar-brand">
            <a class="admin-brand" href="{{ route('home') }}">🏨 <span>Homi</span></a>
            <span class="admin-brand-tag">Admin Panel</span>
        </div>

        <nav class="admin-nav">
            <div class="nav-group">
                <div class="nav-label">Tổng quan</div>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">📊</span><span class="nav-text">Dashboard</span>
                </a>
            </div>
            <div class="nav-group">
                <div class="nav-label">Quản lý</div>
                <a class="nav-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}" href="{{ route('admin.bookings.index') }}">
                    <span class="nav-icon">📋</span><span class="nav-text">Đặt phòng</span>
                    @if (($pendingBookingsCount ?? 0) > 0)
                        <span class="nav-badge">{{ $pendingBookingsCount }}</span>
                    @endif
                </a>
                <a class="nav-link {{ request()->routeIs('admin.room-types.*') ? 'active' : '' }}" href="{{ route('admin.room-types.index') }}">
                    <span class="nav-icon">🛏️</span><span class="nav-text">Loại phòng</span>
                </a>
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                    <span class="nav-icon">👥</span><span class="nav-text">{{ auth()->user()->role === 'admin' ? 'Người dùng' : 'Khách hàng' }}</span>
                </a>
                @if (auth()->user()->role === 'admin')
                    <a class="nav-link {{ request()->routeIs('admin.hotels.*') ? 'active' : '' }}" href="{{ route('admin.hotels.index') }}">
                        <span class="nav-icon">🏨</span><span class="nav-text">Khách sạn</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.promotions.*') ? 'active' : '' }}" href="{{ route('admin.promotions.index') }}">
                        <span class="nav-icon">🏷️</span><span class="nav-text">Khuyến mãi</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                        <span class="nav-icon">⭐</span><span class="nav-text">Đánh giá</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                        <span class="nav-icon">💳</span><span class="nav-text">Thanh toán</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.amenities.*') ? 'active' : '' }}" href="{{ route('admin.amenities.index') }}">
                        <span class="nav-icon">🧰</span><span class="nav-text">Tiện ích</span>
                    </a>
                @endif
            </div>
            @if (auth()->user()->role === 'admin')
                <div class="nav-group">
                    <div class="nav-label">Hệ thống</div>
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                        <span class="nav-icon">⚙️</span><span class="nav-text">Cài đặt</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                        <span class="nav-icon">🕒</span><span class="nav-text">Nhật ký hoạt động</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.database') ? 'active' : '' }}" href="{{ route('admin.database') }}">
                        <span class="nav-icon">🗄️</span><span class="nav-text">Database</span>
                    </a>
                </div>
            @endif
        </nav>

        <div class="admin-sidebar-footer">
            <a class="nav-link" href="{{ route('home') }}"><span class="nav-icon">🌐</span><span class="nav-text">Xem website</span></a>
        </div>
    </aside>

    <div class="admin-content-wrap">

        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" title="Thu gọn menu">☰</button>

            <form class="admin-search input-icon-wrap" method="GET" action="{{ route('admin.bookings.index') }}">
                <span class="input-icon">🔍</span>
                <input class="form-control" type="text" name="search" placeholder="Tìm mã đặt phòng, khách hàng... (Enter)">
            </form>

            <div class="admin-topbar-right">
                <button class="theme-toggle">🌙</button>

                <div class="admin-notif-wrap">
                    <button class="admin-icon-btn" id="notifBtn" title="Thông báo">🔔
                        @if (($pendingBookingsCount ?? 0) > 0)<span class="notif-dot"></span>@endif
                    </button>
                    <div class="admin-dropdown" id="notifDropdown" style="min-width:300px">
                        <div class="admin-dropdown-title">Thông báo</div>
                        @if (($pendingBookingsCount ?? 0) > 0)
                            <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="dropdown-item">
                                <div class="notif-item"><strong>📋 {{ $pendingBookingsCount }} đặt phòng chờ duyệt</strong><span>Cần xác nhận sớm</span></div>
                            </a>
                        @else
                            <div class="notif-item"><strong>✅ Không có đơn chờ duyệt</strong><span>Tất cả đặt phòng đã được xử lý</span></div>
                        @endif
                    </div>
                </div>

                <div class="admin-profile-wrap">
                    <div class="admin-profile" id="profileBtn">
                        <div class="admin-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                        <div class="admin-profile-info">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>{{ auth()->user()->role === 'admin' ? 'Quản trị viên' : 'Nhân viên' }}</span>
                        </div>
                        <span class="caret">▾</span>
                    </div>
                    <div class="admin-dropdown" id="profileDropdown">
                        <a href="{{ route('dashboard') }}">👤 Hồ sơ cá nhân</a>
                        @if (auth()->user()->role === 'admin')
                            <a href="{{ route('admin.settings.index') }}">⚙️ Cài đặt hệ thống</a>
                        @endif
                        <hr>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">🚪 Đăng xuất</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="admin-main">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="{{ asset('admin-assets/js/app.js') }}"></script>
<script src="{{ asset('admin-assets/js/admin-panel.js') }}"></script>
@stack('scripts')
</body>
</html>
