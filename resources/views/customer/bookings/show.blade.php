@extends('layouts.app')

@section('title', 'Đơn ' . $booking->booking_code . ' · Homi')
@section('banner_tag', 'Chi tiết đơn')
@section('banner_title', 'Đơn ' . $booking->booking_code)
@section('banner_subtitle', 'Nhận phòng ' . $booking->check_in->format('d/m/Y') . ' · Trả phòng ' . $booking->check_out->format('d/m/Y'))
@section('banner_badge', $booking->status->label())
@section('banner_badge_class', $booking->status->badgeClass())

@section('content')

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

@if ($booking->status === \App\Enums\BookingStatus::PENDING_DEPOSIT)
    <div
        x-data="{
            expiresAt: {{ $booking->deposit_expires_at ? $booking->deposit_expires_at->timestamp * 1000 : 'null' }},
            remaining: '',
            expired: false,
            toastOpen: true,
            tick() {
                if (!this.expiresAt) return;
                const ms = this.expiresAt - Date.now();
                if (ms <= 0) {
                    this.remaining = '0:00';
                    this.expired = true;
                    return;
                }
                const m = Math.floor(ms / 60000);
                const s = Math.floor((ms % 60000) / 1000);
                this.remaining = m + ':' + String(s).padStart(2, '0');
            },
            scrollToPayment() {
                this.toastOpen = false;
                document.getElementById('payment-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }"
        x-init="tick(); if (expiresAt) setInterval(() => tick(), 1000)"
    >
        {{-- Toast nổi bật ngay khi vào trang, tách biệt khỏi banner tĩnh bên
        dưới để khách chắc chắn nhìn thấy hạn đặt cọc dù đang cuộn ở đâu. --}}
        <div
            x-show="toastOpen && !expired"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            class="fixed inset-x-4 top-4 z-50 sm:inset-x-auto sm:right-4 sm:w-[380px]"
        >
            <div class="flex items-start gap-3 rounded-2xl border-2 border-amber-300 bg-white p-4 shadow-2xl shadow-amber-500/20 dark:border-amber-700 dark:bg-slate-900">
                <div class="relative grid h-11 w-11 shrink-0 place-items-center rounded-full bg-amber-500 text-xl text-white">
                    <span class="absolute inset-0 animate-ping rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative">⏳</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-heading text-sm font-bold text-slate-900 dark:text-white">Đặt phòng thành công!</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-600 dark:text-slate-300">
                        Vui lòng đặt cọc/thanh toán trong <strong class="text-amber-600 dark:text-amber-400" x-text="remaining"></strong> để giữ đơn <strong>{{ $booking->booking_code }}</strong>.
                    </p>
                    <button type="button" @click="scrollToPayment()" class="btn-accent btn-sm mt-2.5">Đặt cọc ngay</button>
                </div>
                <button type="button" @click="toastOpen = false" aria-label="Đóng thông báo" class="shrink-0 rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">✕</button>
            </div>
        </div>

        <div class="card border-2 border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40" :class="{ 'opacity-60': expired }">
            <div class="flex flex-wrap items-center gap-4">
                <div class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-amber-500 text-2xl text-white">⏳</div>
                <div class="flex-1">
                    <h2 class="font-heading text-xl font-bold text-amber-700 dark:text-amber-300">Đặt phòng thành công — chờ bạn đặt cọc/thanh toán</h2>
                    <p class="mt-1 text-sm text-amber-700/80 dark:text-amber-300/80">
                        Mã đơn <strong>{{ $booking->booking_code }}</strong>. Vui lòng đặt cọc 30% hoặc thanh toán đủ trong
                        <strong x-text="remaining"></strong>, nếu không đơn sẽ <strong>tự động hủy</strong> và phòng được nhả lại.
                    </p>
                </div>
            </div>
        </div>
    </div>
@elseif ($booking->status === \App\Enums\BookingStatus::PENDING)
    <div class="card border-2 border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40">
        <div class="flex flex-wrap items-center gap-4">
            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-emerald-500 text-2xl text-white">✓</div>
            <div class="flex-1">
                <h2 class="font-heading text-xl font-bold text-emerald-700 dark:text-emerald-300">Đặt phòng thành công!</h2>
                <p class="mt-1 text-sm text-emerald-700/80 dark:text-emerald-300/80">
                    Mã đơn <strong>{{ $booking->booking_code }}</strong> đang chờ khách sạn xác nhận. Bạn có thể thanh toán ngay bây giờ — không cần chờ xác nhận, nhân viên sẽ đối soát và xác nhận đơn sau khi nhận được thanh toán.
                </p>
            </div>
            <a href="{{ route('customer.bookings.index') }}" class="btn-primary shrink-0">Xem lịch sử đặt phòng</a>
        </div>
    </div>
@endif

<div class="card">
    <span class="section-kicker">Phòng đã đặt</span>
    <div class="table-wrapper mt-2.5">
        <table>
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Loại phòng</th>
                    <th>Số lượng</th>
                    <th>Số khách</th>
                    <th>Giá/đêm</th>
                    <th>Số đêm</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($booking->bookingItems as $item)
                    @php $cover = $item->roomType?->images->first(); @endphp
                    <tr>
                        <td>
                            @if ($cover)
                                <img src="{{ $cover->image_url }}" alt="" class="h-13 w-18 rounded-md object-cover">
                            @else
                                <span class="badge">Chưa có ảnh</span>
                            @endif
                        </td>
                        <td>{{ $item->roomType->name ?? '—' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->adults }} người lớn{{ $item->children ? ', ' . $item->children . ' trẻ em' : '' }}{{ $item->infants ? ', ' . $item->infants . ' sơ sinh' : '' }}</td>
                        <td>{{ number_format($item->price_per_night, 0, ',', '.') }}đ</td>
                        <td>{{ $item->nights }}</td>
                        <td>
                            {{ number_format($item->subtotal + $item->child_surcharge, 0, ',', '.') }}đ
                            @if ($item->child_surcharge > 0)
                                <div class="text-xs text-slate-500 dark:text-slate-400">(gồm {{ number_format($item->child_surcharge, 0, ',', '.') }}đ phụ thu trẻ em)</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($booking->serviceItems->isNotEmpty())
        <span class="section-kicker mt-5 block">Dịch vụ thêm</span>
        <div class="table-wrapper mt-2.5">
            <table>
                <thead>
                    <tr>
                        <th>Dịch vụ</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($booking->serviceItems as $serviceItem)
                        <tr>
                            <td>{{ $serviceItem->service?->name ?? '—' }}</td>
                            <td>{{ $serviceItem->quantity }}</td>
                            <td>{{ number_format($serviceItem->unit_price, 0, ',', '.') }}đ</td>
                            <td>{{ number_format($serviceItem->subtotal, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="info-list mt-5">
        <div class="info-item">
            <span class="label">Ngày nhận phòng</span>
            <span class="value">{{ $booking->check_in->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Ngày trả phòng</span>
            <span class="value">{{ $booking->check_out->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Số đêm</span>
            <span class="value">{{ $booking->nights }}</span>
        </div>
        <div class="info-item">
            <span class="label">Tổng số khách</span>
            <span class="value">{{ $booking->adults }} người lớn{{ $booking->children ? ', ' . $booking->children . ' trẻ em' : '' }}{{ $booking->infants ? ', ' . $booking->infants . ' sơ sinh' : '' }}</span>
        </div>
        @if ($booking->discount_amount > 0)
            <div class="info-item">
                <span class="label">Tạm tính</span>
                <span class="value">{{ number_format($booking->total_amount + $booking->discount_amount, 0, ',', '.') }}đ</span>
            </div>
            @forelse ($booking->promotions as $promo)
                <div class="info-item">
                    <span class="label">Giảm giá ({{ $promo->code }})</span>
                    <span class="value text-accent">-{{ number_format($promo->pivot->discount_amount, 0, ',', '.') }}đ</span>
                </div>
            @empty
                <div class="info-item">
                    <span class="label">Giảm giá {{ $booking->promotion ? '(' . $booking->promotion->code . ')' : '' }}</span>
                    <span class="value text-accent">-{{ number_format($booking->discount_amount, 0, ',', '.') }}đ</span>
                </div>
            @endforelse
        @endif
        <div class="info-item">
            <span class="label">Tổng tiền</span>
            <span class="value text-lg text-primary">{{ number_format($booking->total_amount, 0, ',', '.') }}đ</span>
        </div>
        @if ($booking->note)
            <div class="info-item">
                <span class="label">Yêu cầu đặc biệt</span>
                <span class="value">{{ $booking->note }}</span>
            </div>
        @endif
    </div>
</div>

<div class="card">
    @if ($booking->payment)
        <span class="section-kicker">Trạng thái</span>
        <div class="info-list mt-2.5">
            <div class="info-item">
                <span class="label">Thanh toán</span>
                <span class="value">
                    <span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span>
                </span>
            </div>
            @if ($booking->payment->deposit_paid_at)
                <div class="info-item">
                    <span class="label">Đã đặt cọc</span>
                    <span class="value">{{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ lúc {{ $booking->payment->deposit_paid_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Còn lại (tiền mặt khi nhận phòng)</span>
                    <span class="value">{{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ</span>
                </div>
            @endif
        </div>
    @endif

    @php
        $incidentalInvoice = $booking->incidentalInvoice;
        $incidentalItems = $incidentalInvoice?->items ?? collect();
    @endphp
    @if ($incidentalItems->isNotEmpty())
        <span class="section-kicker mt-5 block">Hóa đơn phát sinh</span>
        <div class="info-list mt-2.5">
            @foreach ($incidentalItems as $item)
                <div class="info-item">
                    <span class="label">{{ $item->description }}</span>
                    <span class="value">{{ number_format($item->amount, 0, ',', '.') }}đ</span>
                </div>
            @endforeach
            <div class="info-item">
                <span class="label">Tổng hóa đơn phát sinh</span>
                <span class="value">
                    {{ number_format($incidentalInvoice->total_amount, 0, ',', '.') }}đ
                    <span class="badge {{ $incidentalInvoice->isPaid() ? 'badge-green' : 'badge-orange' }}">{{ $incidentalInvoice->isPaid() ? 'Đã thanh toán' : 'Thanh toán khi trả phòng' }}</span>
                </span>
            </div>
        </div>
    @endif

    <span class="section-kicker mt-5 block">Thông tin liên hệ</span>
    <div class="info-list mt-2.5">
        <div class="info-item">
            <span class="label">Họ tên</span>
            <span class="value">{{ $booking->customer_name }}</span>
        </div>
        <div class="info-item">
            <span class="label">Điện thoại</span>
            <span class="value">{{ $booking->customer_phone }}</span>
        </div>
        @if ($booking->customer_email)
            <div class="info-item">
                <span class="label">Email</span>
                <span class="value">{{ $booking->customer_email }}</span>
            </div>
        @endif
        @if ($booking->national_id)
            <div class="info-item">
                <span class="label">Số CCCD/CMND</span>
                <span class="value">{{ $booking->national_id }}</span>
            </div>
        @endif
    </div>

    <div class="quick-actions-row">
        <a href="{{ route('customer.bookings.index') }}" class="btn-outline">Quay lại danh sách</a>

        @if ($booking->earlyCheckinRequests->contains(fn ($r) => $r->status === 'pending'))
            <p class="text-xs text-slate-500 dark:text-slate-400">Đơn đang có 1 yêu cầu nhận phòng sớm chờ khách sạn duyệt.</p>
        @elseif ($booking->canRequestEarlyCheckin())
            <a href="{{ route('customer.bookings.early-checkin.create', $booking->id) }}" class="btn-outline w-full text-center">🕒 Yêu cầu nhận phòng sớm</a>
        @elseif ($booking->status === \App\Enums\BookingStatus::CONFIRMED)
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Chỉ có thể yêu cầu nhận phòng sớm trong vòng {{ \App\Services\EarlyCheckinRequestService::REQUEST_WINDOW_HOURS_BEFORE }} giờ so với giờ nhận phòng.
            </p>
        @endif

        @if ($booking->lateCheckoutRequests->contains(fn ($r) => $r->status === 'pending'))
            <p class="text-xs text-slate-500 dark:text-slate-400">Đơn đang có 1 yêu cầu trả phòng muộn chờ khách sạn duyệt.</p>
        @elseif ($booking->canRequestLateCheckout())
            <a href="{{ route('customer.bookings.late-checkout.create', $booking->id) }}" class="btn-outline w-full text-center">🕒 Yêu cầu trả phòng muộn</a>
        @endif

        @if (
            in_array($booking->status, [\App\Enums\BookingStatus::PENDING, \App\Enums\BookingStatus::CONFIRMED], true)
            && $booking->bookingItems->count() === 1
            && ! $booking->roomChangeRequests->contains(fn ($r) => $r->status === 'pending')
        )
            <a href="{{ route('customer.bookings.room-change.create', $booking->id) }}" class="btn-outline w-full text-center">🔁 Yêu cầu đổi phòng</a>
        @elseif ($booking->roomChangeRequests->contains(fn ($r) => $r->status === 'pending'))
            <p class="text-xs text-slate-500 dark:text-slate-400">Đơn đang có 1 yêu cầu đổi phòng chờ khách sạn duyệt.</p>
        @endif

        @if ($booking->canCancelByCustomer())
            @php
                // Hủy đơn đã thanh toán/cọc giờ kích hoạt hoàn tiền THẬT (attemptRefund())
                // — báo trước cho khách biết việc này sẽ xảy ra, kèm % phí hủy
                // theo bậc giờ hiện tại (Booking::cancellationFeePercent()).
                $hasPaidAnything = $booking->payment && $booking->payment->status !== \App\Enums\PaymentStatus::UNPAID;
                $feePercent = $hasPaidAnything ? $booking->cancellationFeePercent() : 0;
                $paidSoFar = (float) ($booking->payment->amount_collected ?? 0);
                if ($paidSoFar <= 0 && $booking->payment && $booking->payment->status === \App\Enums\PaymentStatus::DEPOSIT_PAID) {
                    $paidSoFar = (float) $booking->payment->deposit_amount;
                }
                $estimatedFee = $feePercent > 0 ? min($paidSoFar, round((float) $booking->total_amount * $feePercent / 100)) : 0;

                $cancelConfirmMessage = 'Bạn chắc chắn muốn hủy đơn ' . $booking->booking_code . '?';
                if ($estimatedFee > 0) {
                    $cancelConfirmMessage .= " Phí hủy {$feePercent}% theo chính sách hủy — bạn sẽ mất khoảng " . number_format($estimatedFee, 0, ',', '.') . 'đ, phần còn lại (nếu có) sẽ được hoàn.';
                } elseif ($booking->payment && $booking->payment->status === \App\Enums\PaymentStatus::PAID) {
                    $cancelConfirmMessage .= $booking->payment->method === \App\Enums\PaymentMethod::ONLINE_VNPAY
                        ? ' Hệ thống sẽ tự động gửi yêu cầu hoàn tiền qua VNPay.'
                        : ' Khoản đã thanh toán sẽ được đánh dấu cần hoàn tiền, khách sạn sẽ liên hệ hoàn lại cho bạn.';
                }
            @endphp
            <form method="POST" action="{{ route('customer.bookings.cancel', $booking->id) }}"
                onsubmit="return confirm('{{ $cancelConfirmMessage }}');">
                @csrf
                <button type="submit" class="btn-danger w-full">Hủy đơn</button>
            </form>
            @if ($feePercent > 0)
                <p class="mt-2 text-xs text-red-500">
                    Hủy ngay bây giờ: phí hủy {{ $feePercent }}%{{ $estimatedFee > 0 ? ' (khoảng ' . number_format($estimatedFee, 0, ',', '.') . 'đ)' : '' }}, hoàn {{ 100 - $feePercent }}%.
                </p>
            @else
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Hủy ngay bây giờ: miễn phí, hoàn 100% (nếu đã thanh toán).
                </p>
            @endif
        @endif
    </div>
</div>

@if ($booking->payment && $booking->payment->status === \App\Enums\PaymentStatus::PENDING && $booking->status === \App\Enums\BookingStatus::CHECKED_IN)
    {{-- PENDING trong lúc đang lưu trú (CHECKED_IN) không thể đến từ luồng
    "khách tự báo chuyển khoản" (chỉ cho phép khi đơn còn ở CONFIRMED, xem
    BookingService::markBankTransferPending()) — chắc chắn là do vừa phát
    sinh thêm dịch vụ/phụ phí giữa lúc lưu trú (BookingService::applyExtraCharge()
    mở lại PAID→PENDING, kể cả khi trước đó đã thanh toán xong qua VNPay),
    nên phải kiểm tra nhánh này TRƯỚC nhánh theo method bên dưới — nếu không,
    một đơn đã trả qua VNPay rồi bị cộng thêm phí sẽ hiển thị nhầm thông báo
    "bạn có đóng trang thanh toán giữa chừng không" dù khách đã thanh toán
    xong từ trước, hoặc thông báo "đã ghi nhận chuyển khoản" sai sự thật. --}}
    <div class="card">
        <span class="section-kicker">Thanh toán</span>
        <div class="alert alert-warning mt-2.5">
            Đơn vừa phát sinh thêm phí, cần thanh toán thêm <strong>{{ number_format((float) $booking->payment->amount - (float) $booking->payment->amount_collected, 0, ',', '.') }}đ</strong>. Vui lòng thanh toán qua VNPay bên dưới hoặc liên hệ khách sạn để thanh toán tại quầy.
        </div>
        <form method="POST" action="{{ route('customer.bookings.pay-online', $booking->id) }}" class="mt-3">
            @csrf
            <button type="submit" class="btn-primary w-full">Thanh toán qua VNPay</button>
        </form>
    </div>
@elseif ($booking->payment && $booking->payment->status === \App\Enums\PaymentStatus::PENDING && $booking->payment->method === \App\Enums\PaymentMethod::ONLINE_VNPAY)
    <div class="card">
        <span class="section-kicker">Thanh toán</span>
        <div class="alert alert-warning mt-2.5">
            Đang chờ xác nhận từ VNPay. Nếu bạn đã đóng trang thanh toán giữa chừng hoặc chưa hoàn tất, vui lòng tải lại trang này hoặc bấm thanh toán lại bên dưới.
        </div>
        <form method="POST" action="{{ route('customer.bookings.pay-online', $booking->id) }}" class="mt-3">
            @csrf
            <button type="submit" class="btn-primary w-full">Thanh toán lại qua VNPay</button>
        </form>
    </div>
@elseif ($booking->payment && $booking->payment->status === \App\Enums\PaymentStatus::PENDING)
    <div class="card">
        <span class="section-kicker">Thanh toán</span>
        <div class="alert alert-success mt-2.5">
            Đã ghi nhận bạn chuyển khoản — đang chờ khách sạn xác nhận. Vui lòng chờ, không cần thao tác thêm.
        </div>
    </div>
@elseif ($booking->payment && $booking->payment->status === \App\Enums\PaymentStatus::DEPOSIT_PAID)
    <div class="card">
        <span class="section-kicker">Thanh toán</span>
        <div class="alert alert-success mt-2.5">
            Đã đặt cọc {{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ (30%).
            Vui lòng thanh toán <strong>{{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ</strong> còn lại bằng tiền mặt khi nhận phòng.
        </div>
    </div>
@elseif ($booking->canMarkPaymentAsPaid())
    <div class="card" id="payment-section">
        <span class="section-kicker">Thanh toán</span>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            @if ($booking->status === \App\Enums\BookingStatus::PENDING_DEPOSIT)
                Chọn 1 trong 2: đặt cọc 30% (mô phỏng) hoặc chuyển khoản 100% qua mã QR, trước khi hết hạn giữ chỗ ở trên.
            @elseif ($booking->status === \App\Enums\BookingStatus::PENDING)
                Đơn đang chờ xác nhận — bạn có thể thanh toán ngay, không cần chờ khách sạn duyệt đơn trước.
            @else
                Đơn đã được xác nhận. Chọn một trong các hình thức thanh toán bên dưới (chuyển khoản 100% qua QR sẽ được nhân viên đối soát và xác nhận thủ công; đặt cọc 30% là demo).
            @endif
        </p>

        @php
            $outstandingAmount = round((float) $booking->payment->amount - (float) $booking->payment->amount_collected, 2);
        @endphp

        <div class="grid gap-4">
            <div
                x-data="{
                    showQr: false,
                    openQr() { this.showQr = true; },
                    closeQr() { this.showQr = false; }
                }"
            >
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="mb-2 flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-primary-light text-primary dark:bg-primary/15">🏦</span>
                        Chuyển khoản ngân hàng — quét mã QR
                    </div>
                    <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">Chuyển khoản <strong>{{ number_format($outstandingAmount, 0, ',', '.') }}đ</strong> (100%) tới tài khoản khách sạn qua mã QR. Sau khi chuyển, nhân viên đối soát và xác nhận đơn.</p>
                    <button type="button" @click.stop="openQr()" class="btn-primary w-full">Chuyển khoản 100% — quét mã QR</button>
                </div>

                <form id="bank-transfer-form" method="POST" action="{{ route('customer.bookings.pay-bank-transfer', $booking->id) }}" class="hidden">
                    @csrf
                </form>

                {{-- QR chuyển khoản THẬT (VietQR, tài khoản khách sạn cấu hình ở
                config('services.bank_transfer')) — khác với modal "Đặt cọc 30%"
                bên dưới (mô phỏng): xác nhận ở đây KHÔNG tự đánh dấu đã thanh
                toán, chỉ báo "chờ xác nhận" qua BookingService::
                markBankTransferPending(), nhân viên đối soát sao kê thủ công. --}}
                <template x-if="showQr">
                    <div class="fixed inset-0 z-50 grid place-items-center bg-slate-900/60 p-4" @keydown.escape.window="closeQr()">
                        <div @click.outside="closeQr()" class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-primary-light text-primary dark:bg-primary/15">🏦</span>
                                    {{ config('services.bank_transfer.bank_name') }}
                                </div>
                                <button type="button" @click="closeQr()" aria-label="Đóng" class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">✕</button>
                            </div>

                            <p class="text-center text-sm font-bold text-slate-900 dark:text-white">Quét mã để chuyển khoản</p>
                            <p class="mt-1 text-center text-2xl font-extrabold text-primary">{{ number_format($outstandingAmount, 0, ',', '.') }}đ</p>

                            <div class="my-4 grid place-items-center">
                                <div class="rounded-xl border-4 border-primary p-2">
                                    <img
                                        src="https://img.vietqr.io/image/{{ config('services.bank_transfer.bin') }}-{{ config('services.bank_transfer.account_no') }}-qr_only.png?amount={{ (int) round($outstandingAmount) }}&addInfo={{ urlencode($booking->booking_code) }}&accountName={{ urlencode(config('services.bank_transfer.account_name')) }}"
                                        alt="QR chuyển khoản {{ config('services.bank_transfer.bank_name') }}"
                                        class="h-[220px] w-[220px] object-contain"
                                        loading="lazy"
                                    >
                                </div>
                            </div>

                            <div class="space-y-1 rounded-lg bg-slate-50 p-3 text-xs leading-relaxed dark:bg-slate-800">
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Ngân hàng</span><strong>{{ config('services.bank_transfer.bank_name') }}</strong></div>
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Số tài khoản</span><strong class="font-mono">{{ config('services.bank_transfer.account_no') }}</strong></div>
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Chủ tài khoản</span><strong>{{ config('services.bank_transfer.account_name') }}</strong></div>
                                <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Nội dung CK</span><strong>{{ $booking->booking_code }}</strong></div>
                            </div>

                            <p class="mt-2 text-center text-[11px] text-slate-500 dark:text-slate-400">Camera lỗi không quét được mã? Mở app ngân hàng, nhập tay số tài khoản ở trên.</p>

                            <p class="mt-3 rounded-lg bg-amber-50 p-2 text-center text-[11px] leading-relaxed text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                ⚠️ Đây là chuyển khoản <strong>thật</strong> tới tài khoản khách sạn. Sau khi chuyển xong, bấm "Tôi đã chuyển khoản" — nhân viên sẽ đối soát sao kê và xác nhận đơn, không tự động xác nhận ngay.
                            </p>

                            <div class="mt-4 flex gap-2.5">
                                <button type="button" @click="closeQr()" class="btn-outline flex-1">Huỷ</button>
                                <button type="submit" form="bank-transfer-form" class="btn-primary flex-1">Tôi đã chuyển khoản</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

                @if ($booking->canPayDeposit())
                    <div
                        x-data="{
                            showQr: false,
                            gateway: 'vnpay',
                            qrSeconds: 300,
                            qrTimer: null,
                            openQr() {
                                this.showQr = true;
                                this.qrSeconds = 300;
                                clearInterval(this.qrTimer);
                                this.qrTimer = setInterval(() => {
                                    this.qrSeconds = this.qrSeconds > 0 ? this.qrSeconds - 1 : 300;
                                }, 1000);
                                this.$nextTick(() => this.drawQr());
                            },
                            closeQr() {
                                this.showQr = false;
                                clearInterval(this.qrTimer);
                            },
                            drawQr() {
                                homiDrawFakeQr(this.$refs.qrCanvas, '{{ $booking->booking_code }}:' + this.gateway, this.gateway === 'vnpay' ? '#1d4ed8' : '#d6249f');
                            },
                            qrLabel() {
                                const m = Math.floor(this.qrSeconds / 60);
                                const s = this.qrSeconds % 60;
                                return m + ':' + String(s).padStart(2, '0');
                            }
                        }"
                    >
                        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <div class="mb-2 flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-accent-light text-accent-dark dark:bg-accent/15">🏦</span>
                                Đặt cọc 30%
                            </div>
                            <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">Đặt cọc {{ number_format($booking->depositAmount(), 0, ',', '.') }}đ, trả {{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ còn lại bằng tiền mặt khi nhận phòng.</p>
                            <button type="button" @click.stop="openQr()" class="btn-outline w-full">Đặt cọc 30% — quét mã QR</button>
                        </div>

                        <form id="deposit-form" method="POST" action="{{ route('customer.bookings.pay-deposit', $booking->id) }}" class="hidden">
                            @csrf
                        </form>

                        {{-- Modal mô phỏng giao diện quét mã VNPay/MoMo — thuần minh họa,
                        không gọi API cổng thanh toán thật; xác nhận trong modal submit
                        thẳng vào form ẩn ở trên (payDepositDemo() vẫn xử lý phía server). --}}
                        <template x-if="showQr">
                            <div class="fixed inset-0 z-50 grid place-items-center bg-slate-900/60 p-4" @keydown.escape.window="closeQr()">
                                <div @click.outside="closeQr()" class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
                                    <div class="mb-4 flex items-center justify-between">
                                        <div class="flex overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                                            <button type="button" @click="gateway = 'vnpay'; drawQr()" class="px-3 py-1.5 text-xs font-bold transition" :class="gateway === 'vnpay' ? 'bg-blue-600 text-white' : 'bg-slate-50 text-slate-500 dark:bg-slate-800'">VNPay</button>
                                            <button type="button" @click="gateway = 'momo'; drawQr()" class="px-3 py-1.5 text-xs font-bold transition" :class="gateway === 'momo' ? 'bg-pink-600 text-white' : 'bg-slate-50 text-slate-500 dark:bg-slate-800'">MoMo</button>
                                        </div>
                                        <button type="button" @click="closeQr()" aria-label="Đóng" class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800">✕</button>
                                    </div>

                                    <p class="text-center text-sm font-bold text-slate-900 dark:text-white" x-text="gateway === 'vnpay' ? 'Quét mã bằng ứng dụng ngân hàng / VNPay' : 'Quét mã bằng ứng dụng MoMo'"></p>
                                    <p class="mt-1 text-center text-2xl font-extrabold" :class="gateway === 'vnpay' ? 'text-blue-600' : 'text-pink-600'">{{ number_format($booking->depositAmount(), 0, ',', '.') }}đ</p>

                                    <div class="my-4 grid place-items-center">
                                        <div class="rounded-xl border-4 p-2 transition-colors" :class="gateway === 'vnpay' ? 'border-blue-600' : 'border-pink-600'">
                                            <canvas x-ref="qrCanvas" width="200" height="200"></canvas>
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-slate-50 p-3 text-xs leading-relaxed dark:bg-slate-800">
                                        <div class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">Nội dung</span><strong>{{ $booking->booking_code }}</strong></div>
                                        <div class="mt-1 flex justify-between"><span class="text-slate-500 dark:text-slate-400">Mã QR hết hạn sau</span><strong x-text="qrLabel()"></strong></div>
                                    </div>

                                    <div class="mt-3 flex items-center justify-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                        <span>Đang chờ quét mã</span>
                                        <span class="flex gap-0.5">
                                            <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay: 0ms"></span>
                                            <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay: 150ms"></span>
                                            <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay: 300ms"></span>
                                        </span>
                                    </div>

                                    <p class="mt-3 rounded-lg bg-amber-50 p-2 text-center text-[11px] leading-relaxed text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                        ⚠️ Đây là giao diện <strong>mô phỏng</strong> để minh họa — không kết nối cổng thanh toán thật, không có giao dịch nào thực sự diễn ra.
                                    </p>

                                    <div class="mt-4 flex gap-2.5">
                                        <button type="button" @click="closeQr()" class="btn-outline flex-1">Huỷ</button>
                                        <button type="submit" form="deposit-form" class="btn-primary flex-1">Đã quét & thanh toán</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                @endif

                @unless ($booking->status === \App\Enums\BookingStatus::PENDING_DEPOSIT)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
                        <div class="mb-1 flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300">🏨</span>
                            Thanh toán tại khách sạn
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Mặc định — thanh toán bằng tiền mặt khi nhận phòng, không cần thao tác gì thêm.</p>
                    </div>
                @endunless
        </div>
    </div>
@elseif ($booking->payment && $booking->payment->isPaid())
    <div class="card flex items-center justify-between">
        <div>
            <span class="section-kicker">Hóa đơn</span>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Đã thanh toán</h3>
        </div>
        <a href="{{ route('customer.bookings.invoice', $booking->id) }}" target="_blank" class="btn-outline btn-sm">🖨 Xem/In hóa đơn</a>
    </div>
@endif

@if ($booking->payment && $booking->payment->statusLogs->isNotEmpty())
    <div class="card">
        <span class="section-kicker">Lịch sử thanh toán</span>
        <div class="table-wrapper mt-2.5">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Từ</th>
                        <th>Đến</th>
                        <th>Người thực hiện</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($booking->payment->statusLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->from_status?->label() ?? '—' }}</td>
                            <td>{{ $log->to_status->label() }}</td>
                            <td>{{ $log->changedBy?->name ?? 'Khách hàng' }}</td>
                            <td>{{ $log->note ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($booking->canPayDeposit())
    <script>
        // Vẽ mã QR minh họa (không mã hóa dữ liệu thật, chỉ để giống giao
        // diện quét mã của cổng thanh toán) — seed theo mã đơn + gateway nên
        // pattern ổn định giữa các lần vẽ lại, không nhấp nháy ngẫu nhiên.
        function homiDrawFakeQr(canvas, seedText, accentColor) {
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const size = canvas.width;
            const grid = 21;
            const cell = size / grid;

            let h = 2166136261;
            for (let i = 0; i < seedText.length; i++) {
                h ^= seedText.charCodeAt(i);
                h = Math.imul(h, 16777619);
            }
            let seed = h >>> 0;
            const rand = () => {
                seed |= 0; seed = (seed + 0x6D2B79F5) | 0;
                let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
                t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
                return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
            };

            ctx.clearRect(0, 0, size, size);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, size, size);

            const drawFinder = (gx, gy) => {
                ctx.fillStyle = '#0f172a';
                ctx.fillRect(gx * cell, gy * cell, 7 * cell, 7 * cell);
                ctx.fillStyle = '#ffffff';
                ctx.fillRect((gx + 1) * cell, (gy + 1) * cell, 5 * cell, 5 * cell);
                ctx.fillStyle = '#0f172a';
                ctx.fillRect((gx + 2) * cell, (gy + 2) * cell, 3 * cell, 3 * cell);
            };

            const inFinderZone = (x, y) => (
                (x < 8 && y < 8) || (x >= grid - 8 && y < 8) || (x < 8 && y >= grid - 8)
            );

            for (let y = 0; y < grid; y++) {
                for (let x = 0; x < grid; x++) {
                    if (inFinderZone(x, y)) continue;
                    if (rand() > 0.55) {
                        ctx.fillStyle = '#0f172a';
                        ctx.fillRect(x * cell, y * cell, cell, cell);
                    }
                }
            }

            drawFinder(0, 0);
            drawFinder(grid - 7, 0);
            drawFinder(0, grid - 7);

            const badge = size * 0.22;
            ctx.fillStyle = accentColor;
            ctx.fillRect((size - badge) / 2, (size - badge) / 2, badge, badge);
            ctx.fillStyle = '#ffffff';
            ctx.font = `bold ${Math.round(badge * 0.32)}px sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(seedText.includes(':momo') ? 'MoMo' : 'VNPay', size / 2, size / 2 + 1);
        }
    </script>
@endif
@endsection
