<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản trị · Homi')</title>
    <style>
        :root {
            --primary: #1e5eff;
            --primary-dark: #1147c9;
            --primary-soft: #edf4ff;
            --sidebar-bg: #0b1530;
            --sidebar-text: #b9c6e8;
            --white: #ffffff;
            --text: #1f2a44;
            --muted: #6c7a96;
            --border: #d6e4ff;
            --bg: #f4f8ff;
            --success: #1a9b5b;
            --danger: #d93025;
            --warning: #b15c00;
            --radius-lg: 20px;
            --radius-md: 14px;
            --radius-sm: 10px;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a { text-decoration: none; color: var(--primary); }

        .admin-shell {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 248px;
            flex-shrink: 0;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .sidebar-brand {
            color: var(--white);
            font-size: 22px;
            font-weight: 800;
            padding: 0 8px 20px;
        }

        .sidebar-brand small {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--sidebar-text);
        }

        .sidebar a {
            color: var(--sidebar-text);
            padding: 11px 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.15s ease;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.06);
            color: var(--white);
        }

        .sidebar a.active {
            background: var(--primary);
            color: var(--white);
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .main {
            flex: 1;
            min-width: 0;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 28px;
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .topbar-title { font-size: 18px; font-weight: 800; }
        .topbar-sub { color: var(--muted); font-size: 13px; margin-top: 2px; }

        .content { padding: 28px; }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: 0 12px 28px rgba(17, 71, 201, 0.07);
            padding: 24px;
        }

        .card + .card { margin-top: 20px; }

        .section-kicker {
            display: inline-block;
            margin-bottom: 6px;
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .section-title { margin: 0 0 8px; font-size: 22px; line-height: 1.2; }
        .section-desc { margin: 0; color: var(--muted); line-height: 1.6; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            padding: 10px 16px;
            cursor: pointer;
        }

        .btn-primary { background: var(--primary); color: var(--white); }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: var(--white); color: var(--primary); border: 1px solid var(--border); }
        .btn-outline:hover { background: var(--primary-soft); }
        .btn-danger { background: #fdeceb; color: var(--danger); border: 1px solid #f6c8c3; }
        .btn-danger:hover { background: #fbdedb; }
        .btn-sm { padding: 7px 11px; font-size: 13px; border-radius: 8px; }
        .btn-block { width: 100%; }

        .page-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .form-grid { display: grid; gap: 16px; }
        .form-group { display: grid; gap: 6px; }
        label { font-size: 13px; font-weight: 700; }

        input, select, textarea {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fbfdff;
            padding: 10px 12px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text);
            outline: none;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 94, 255, 0.10);
        }

        textarea { resize: vertical; }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 8px;
        }

        .checkbox-item { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; }
        .checkbox-item input { width: auto; }

        .filter-bar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .filter-bar input, .filter-bar select { width: auto; min-width: 180px; }

        .alert { border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: #eafaf1; border: 1px solid #bfeed4; color: var(--success); }
        .alert-danger { background: #fff1f0; border: 1px solid #ffd1cc; color: var(--danger); }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-blue { background: #e8f0ff; color: #1850d8; }
        .badge-green { background: #e8f8ef; color: #1a8d55; }
        .badge-orange { background: #fff3e0; color: var(--warning); }
        .badge-red { background: #fdeceb; color: var(--danger); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px;
        }

        .stat-label { color: var(--muted); font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .stat-value { font-size: 22px; font-weight: 800; }

        .table-wrapper { width: 100%; overflow: auto; border: 1px solid var(--border); border-radius: 14px; }
        table { width: 100%; border-collapse: collapse; background: var(--white); }
        thead th {
            background: var(--primary-soft);
            color: #23408b;
            font-size: 12px;
            font-weight: 800;
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        tbody td { padding: 11px 12px; border-bottom: 1px solid #eef3ff; font-size: 13px; vertical-align: top; }
        tbody tr:hover { background: #f6f9ff; }

        .empty-box {
            padding: 16px;
            border-radius: 12px;
            background: #f8fbff;
            border: 1px dashed var(--border);
            color: var(--muted);
        }

        .action-row { display: flex; flex-wrap: wrap; gap: 6px; }
        .action-row form { margin: 0; display: inline; }

        @media (max-width: 900px) {
            .admin-shell { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; }
            .sidebar-footer { margin-top: 0; border-top: none; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
    @stack('styles')
</head>

<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">
                Homi <small>Khu vực quản trị</small>
            </div>

            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Tổng quan</a>
            <a href="{{ route('admin.hotel-info.show') }}" class="{{ request()->routeIs('admin.hotel-info.*') ? 'active' : '' }}">Thông tin khách sạn</a>
            <a href="{{ route('admin.room-types.index') }}" class="{{ request()->routeIs('admin.room-types.*') ? 'active' : '' }}">Loại phòng</a>
            <a href="{{ route('admin.bookings.index') }}" class="{{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">Đơn đặt phòng</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Người dùng</a>
            <a href="{{ route('admin.database') }}" class="{{ request()->routeIs('admin.database') ? 'active' : '' }}">Database</a>

            <div class="sidebar-footer">
                <a href="{{ route('home') }}">← Về trang khách hàng</a>
                <form method="POST" action="{{ route('admin.logout') }}" style="margin: 8px 0 0;">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-block" style="background: rgba(255,255,255,0.06); color: #fff; border-color: rgba(255,255,255,0.14);">Đăng xuất</button>
                </form>
            </div>
        </aside>

        <div class="main">
            <header class="topbar">
                <div>
                    <div class="topbar-title">@yield('page_title', 'Quản trị')</div>
                    <div class="topbar-sub">@yield('page_subtitle', '')</div>
                </div>
                <div style="font-size: 13px; color: var(--muted);">
                    {{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role) }}
                </div>
            </header>

            <div class="content">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
</body>

</html>
