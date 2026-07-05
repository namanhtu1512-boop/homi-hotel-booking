<footer class="mt-16 border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
    <div class="mx-auto grid w-[min(1180px,calc(100%-32px))] gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <div class="font-heading text-2xl font-extrabold text-primary">Homi</div>
            <p class="mt-3 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                Nền tảng đặt phòng khách sạn hiện đại — trải nghiệm đặt phòng nhanh chóng, minh bạch và đáng tin cậy.
            </p>
            <div class="mt-4 flex gap-3">
                @foreach (['f', 'in', 'ig'] as $icon)
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-xs font-bold text-primary shadow-sm dark:bg-slate-800">{{ $icon }}</span>
                @endforeach
            </div>
        </div>

        <div>
            <div class="mb-3 text-sm font-bold text-slate-900 dark:text-white">Liên kết nhanh</div>
            <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                <li><a href="{{ route('home') }}" class="hover:text-primary">Trang chủ</a></li>
                <li><a href="{{ route('rooms.index') }}" class="hover:text-primary">Danh sách phòng</a></li>
                @if (Route::has('promotions.index'))
                    <li><a href="{{ route('promotions.index') }}" class="hover:text-primary">Khuyến mãi</a></li>
                @endif
                @if (Route::has('news.index'))
                    <li><a href="{{ route('news.index') }}" class="hover:text-primary">Tin tức</a></li>
                @endif
                @if (Route::has('contact.show'))
                    <li><a href="{{ route('contact.show') }}" class="hover:text-primary">Liên hệ</a></li>
                @endif
                <li><a href="{{ route('about') }}" class="hover:text-primary">Giới thiệu</a></li>
            </ul>
        </div>

        <div>
            <div class="mb-3 text-sm font-bold text-slate-900 dark:text-white">Hỗ trợ khách hàng</div>
            <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                <li><a href="{{ route('login') }}" class="hover:text-primary">Đăng nhập</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-primary">Đăng ký</a></li>
                @auth
                    <li><a href="{{ route('customer.bookings.index') }}" class="hover:text-primary">Lịch sử đặt phòng</a></li>
                @endauth
            </ul>
        </div>

        <div>
            <div class="mb-3 text-sm font-bold text-slate-900 dark:text-white">Liên hệ</div>
            <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                <li>{{ $footerHotel->address ?? 'Đang cập nhật địa chỉ' }}</li>
                <li>{{ $footerHotel->phone ?? 'Đang cập nhật SĐT' }}</li>
                <li>{{ $footerHotel->email ?? 'Đang cập nhật email' }}</li>
            </ul>
        </div>
    </div>

    <div class="border-t border-slate-200 py-5 text-center text-xs text-slate-400 dark:border-slate-800">
        &copy; {{ date('Y') }} Homi Hotel Booking. Bảo lưu mọi quyền.
    </div>
</footer>
