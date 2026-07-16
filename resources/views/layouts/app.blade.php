<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Homi · Đặt phòng khách sạn')</title>
    <meta name="description" content="@yield('meta_description', 'Homi Hotel — đặt phòng trực tiếp, xem phòng trống theo ngày, giá minh bạch, xác nhận nhanh.')">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Homi · Đặt phòng khách sạn')">
    <meta property="og:description" content="@yield('meta_description', 'Homi Hotel — đặt phòng trực tiếp, xem phòng trống theo ngày, giá minh bạch, xác nhận nhanh.')">
    @include('partials._theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-slate-800 dark:text-slate-100">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex w-[min(1180px,calc(100%-32px))] items-center justify-between gap-4 py-4">
            <a href="{{ route('home') }}" class="font-heading text-2xl font-extrabold text-primary">Homi</a>

            <nav class="hidden items-center gap-1 lg:flex">
                <a href="{{ route('home') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800">Trang chủ</a>
                <a href="{{ route('rooms.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800">Khách sạn</a>
                @if (Route::has('promotions.index'))
                    <a href="{{ route('promotions.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800">Khuyến mãi</a>
                @endif
                @if (Route::has('news.index'))
                    <a href="{{ route('news.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800">Tin tức</a>
                @endif
                @if (Route::has('contact.show'))
                    <a href="{{ route('contact.show') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800">Liên hệ</a>
                @endif
                @if (Route::has('group-bookings.show'))
                    <a href="{{ route('group-bookings.show') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-800">Đặt đoàn/nhóm</a>
                @endif
            </nav>

            <div class="flex items-center gap-2">
                <button type="button" onclick="homiToggleTheme()" aria-label="Đổi giao diện sáng/tối"
                    class="grid h-10 w-10 place-items-center rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5M4.5 12H3m15.36 6.36-1.06-1.06M6.7 6.7 5.64 5.64m12.72 0-1.06 1.06M6.7 17.3l-1.06 1.06M12 7.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z"/></svg>
                    <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                </button>

                <div class="hidden items-center gap-2 sm:flex">
                    @auth
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="btn-outline btn-sm">Trang quản trị</a>
                        @elseif (auth()->user()->isStaff())
                            <a href="{{ route('staff.dashboard') }}" class="btn-outline btn-sm">Khu vực nhân viên</a>
                        @else
                            <a href="{{ route('customer.wishlist.index') }}" class="btn-outline btn-sm">Yêu thích ({{ auth()->user()->wishlistItems()->count() }})</a>
                            <a href="{{ route('customer.bookings.index') }}" class="btn-outline btn-sm">Đơn của tôi</a>
                            <a href="{{ route('customer.chat.index') }}" class="btn-outline btn-sm">💬 Hỗ trợ{{ ($customerChatUnreadCount ?? 0) > 0 ? ' (' . $customerChatUnreadCount . ')' : '' }}</a>
                            @include('partials._notification-bell')
                            <a href="{{ route('customer.profile.show') }}" class="btn-outline btn-sm">Tài khoản</a>
                            <a href="{{ route('customer.bookings.create') }}" class="btn-primary btn-sm">Đặt phòng</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn-outline btn-sm">Đăng xuất</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-outline btn-sm">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="btn-primary btn-sm">Đăng ký</a>
                    @endauth
                </div>

                <button type="button" onclick="document.getElementById('homi-mobile-nav').classList.toggle('hidden')" aria-label="Mở menu"
                    class="grid h-10 w-10 place-items-center rounded-full text-slate-500 hover:bg-slate-100 sm:hidden dark:text-slate-300 dark:hover:bg-slate-800">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
                </button>
            </div>
        </div>

        <div id="homi-mobile-nav" class="hidden border-t border-slate-200 px-4 py-3 sm:hidden dark:border-slate-800">
            <div class="flex flex-col gap-1">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Trang chủ</a>
                <a href="{{ route('rooms.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Khách sạn</a>
                @if (Route::has('promotions.index'))
                    <a href="{{ route('promotions.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Khuyến mãi</a>
                @endif
                @if (Route::has('news.index'))
                    <a href="{{ route('news.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Tin tức</a>
                @endif
                @if (Route::has('contact.show'))
                    <a href="{{ route('contact.show') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Liên hệ</a>
                @endif
                @if (Route::has('group-bookings.show'))
                    <a href="{{ route('group-bookings.show') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Đặt đoàn/nhóm</a>
                @endif
                <div class="my-2 border-t border-slate-200 dark:border-slate-800"></div>
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Trang quản trị</a>
                    @elseif (auth()->user()->isStaff())
                        <a href="{{ route('staff.dashboard') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Khu vực nhân viên</a>
                    @else
                        <a href="{{ route('customer.wishlist.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Yêu thích</a>
                        <a href="{{ route('customer.bookings.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Đơn của tôi</a>
                        <a href="{{ route('customer.chat.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">💬 Hỗ trợ{{ ($customerChatUnreadCount ?? 0) > 0 ? ' (' . $customerChatUnreadCount . ')' : '' }}</a>
                        <a href="{{ route('customer.profile.show') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Tài khoản</a>
                        <a href="{{ route('customer.bookings.create') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-primary hover:bg-slate-100 dark:hover:bg-slate-800">Đặt phòng</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Đăng xuất</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-primary hover:bg-slate-100 dark:hover:bg-slate-800">Đăng ký</a>
                @endauth
            </div>
        </div>
    </header>

    <section class="relative overflow-hidden text-white">
        @hasSection('hero_bg_image')
            <img src="@yield('hero_bg_image')" alt="" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-900/90 via-primary/80 to-blue-600/70"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-blue-700 via-primary to-blue-400"></div>
        @endif
        <div class="absolute -top-24 -right-16 h-72 w-72 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-20 -left-14 h-56 w-56 rounded-full bg-white/10"></div>

        <div class="relative mx-auto w-[min(1180px,calc(100%-32px))] py-8 sm:py-10">
            <span class="mb-4 inline-block rounded-full bg-white/15 px-3.5 py-1.5 text-xs font-bold tracking-wide uppercase">@yield('banner_tag', 'Homi Hotel Booking')</span>
            <h1 class="max-w-2xl text-3xl leading-tight font-extrabold sm:text-4xl">@yield('banner_title', 'Hệ thống quản lý đặt phòng Homi')</h1>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <p class="max-w-xl text-sm leading-relaxed text-white/90 sm:text-base">@yield('banner_subtitle', 'Giao diện hiện đại, rõ ràng, dễ thao tác để quản lý tài khoản, khách sạn, loại phòng và dữ liệu đặt phòng.')</p>
                @hasSection('banner_badge')
                    <span class="badge @yield('banner_badge_class', 'badge-blue')">@yield('banner_badge')</span>
                @endif
            </div>

            @hasSection('hero_extra')
                <div class="mt-6">
                    @yield('hero_extra')
                </div>
            @endif
        </div>
    </section>

    <main class="relative -mt-6 pb-16">
        <div class="mx-auto w-[min(1180px,calc(100%-32px))] space-y-6">
            @yield('content')
        </div>
    </main>

    @include('partials._footer')
</body>

</html>
