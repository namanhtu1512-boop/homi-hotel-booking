<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản trị · Homi')</title>
    @include('partials._theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-slate-50 font-sans text-slate-800 dark:bg-slate-950 dark:text-slate-100">
    <div class="flex min-h-screen flex-col md:flex-row">
        <aside class="flex shrink-0 flex-col gap-1 bg-slate-950 p-4 text-slate-300 md:w-64 md:p-5">
            <div class="px-2 pb-5">
                <div class="font-heading text-xl font-extrabold text-white">Homi</div>
                <small class="text-xs font-semibold text-slate-400">Khu vực quản trị</small>
            </div>

            <nav class="flex flex-row flex-wrap gap-1 md:flex-col md:flex-nowrap">
                @php
                    $adminLinks = [
                        ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Tổng quan'],
                        ['route' => 'admin.hotel-info.show', 'pattern' => 'admin.hotel-info.*', 'label' => 'Thông tin khách sạn'],
                        ['route' => 'admin.room-types.index', 'pattern' => 'admin.room-types.*', 'label' => 'Loại phòng'],
                        ['route' => 'admin.rooms.index', 'pattern' => 'admin.rooms.*', 'label' => 'Phòng vật lý'],
                        ['route' => 'admin.bookings.index', 'pattern' => 'admin.bookings.*', 'label' => 'Đơn đặt phòng'],
                        ['route' => 'admin.payments.index', 'pattern' => 'admin.payments.*', 'label' => 'Thanh toán'],
                        ['route' => 'admin.customers.index', 'pattern' => 'admin.customers.*', 'label' => 'Khách hàng'],
                        ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'label' => 'Người dùng'],
                        ['route' => 'admin.promotions.index', 'pattern' => 'admin.promotions.*', 'label' => 'Khuyến mãi'],
                        ['route' => 'admin.seasonal-rates.index', 'pattern' => 'admin.seasonal-rates.*', 'label' => 'Giá theo mùa'],
                        ['route' => 'admin.services.index', 'pattern' => 'admin.services.*', 'label' => 'Dịch vụ'],
                        ['route' => 'admin.news.index', 'pattern' => 'admin.news.*', 'label' => 'Tin tức'],
                        ['route' => 'admin.banners.index', 'pattern' => 'admin.banners.*', 'label' => 'Banner'],
                        ['route' => 'admin.reviews.index', 'pattern' => 'admin.reviews.*', 'label' => 'Đánh giá'],
                        ['route' => 'admin.contact-messages.index', 'pattern' => 'admin.contact-messages.*', 'label' => 'Liên hệ'],
                        ['route' => 'admin.group-bookings.index', 'pattern' => 'admin.group-bookings.*', 'label' => 'Đặt đoàn/nhóm'],
                        ['route' => 'admin.database', 'pattern' => 'admin.database', 'label' => 'Database'],
                    ];
                @endphp

                @foreach ($adminLinks as $link)
                    @if (Route::has($link['route']))
                        <a href="{{ route($link['route']) }}"
                            class="rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($link['pattern']) ? 'bg-primary text-white' : 'hover:bg-white/5 hover:text-white' }}">
                            {{ $link['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="mt-auto border-t border-white/10 pt-4">
                <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-white/5 hover:text-white">← Về trang khách hàng</a>
                <form method="POST" action="{{ route('admin.logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full rounded-lg border border-white/15 bg-white/5 px-3 py-2 text-sm font-semibold text-white hover:bg-white/10">Đăng xuất</button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4 dark:border-slate-800 dark:bg-slate-900">
                <div>
                    <div class="text-lg font-extrabold text-slate-900 dark:text-white">@yield('page_title', 'Quản trị')</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">@yield('page_subtitle', '')</div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="homiToggleTheme()" aria-label="Đổi giao diện sáng/tối"
                        class="grid h-10 w-10 place-items-center rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5M4.5 12H3m15.36 6.36-1.06-1.06M6.7 6.7 5.64 5.64m12.72 0-1.06 1.06M6.7 17.3l-1.06 1.06M12 7.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z"/></svg>
                        <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                    </button>
                    <div class="hidden text-right text-sm sm:block">
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-slate-400">{{ ucfirst(auth()->user()->role) }}</div>
                    </div>
                </div>
            </header>

            <div class="space-y-4 p-6">
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

    @stack('scripts')
</body>

</html>
