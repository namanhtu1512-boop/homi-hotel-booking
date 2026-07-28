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

@if ($booking->status === \App\Enums\BookingStatus::PENDING)
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
    </div>

    <div class="quick-actions-row">
        <a href="{{ route('customer.bookings.index') }}" class="btn-outline">Quay lại danh sách</a>

        @if ($booking->status === \App\Enums\BookingStatus::CONFIRMED)
            {{-- Nhận phòng sớm hiện được tự động cộng phụ phí (không cần
            duyệt trước) — nút này chỉ để khách CHỦ ĐỘNG báo trước và trao
            đổi với khách sạn (VD hỏi còn phòng để nhận sớm không), gửi
            thẳng vào hộp thư hỗ trợ chung đã có sẵn thay vì phải mở riêng
            trang chat rồi tự gõ lại mã đơn. --}}
            <form method="POST" action="{{ route('customer.chat.store') }}"
                onsubmit="return confirm('Gửi yêu cầu nhận phòng sớm cho đơn {{ $booking->booking_code }} tới khách sạn qua tin nhắn hỗ trợ?');">
                @csrf
                <input type="hidden" name="body" value="[Yêu cầu nhận phòng sớm] Đơn {{ $booking->booking_code }}, dự kiến nhận phòng ngày {{ $booking->check_in->format('d/m/Y') }}. Tôi muốn hỏi khách sạn có thể sắp xếp cho tôi nhận phòng sớm hơn giờ chuẩn được không, xin phản hồi giúp tôi ạ.">
                <button type="submit" class="btn-outline w-full">💬 Yêu cầu nhận phòng sớm</button>
            </form>
        @endif

        @if ($booking->canCancelByCustomer())
            @php
                // Hủy đơn đã thanh toán đủ giờ kích hoạt hoàn tiền THẬT (attemptRefund())
                // — báo trước cho khách biết việc này sẽ xảy ra thay vì chỉ hỏi
                // chung chung như trước khi VNPay còn là mô phỏng.
                $cancelConfirmMessage = 'Bạn chắc chắn muốn hủy đơn ' . $booking->booking_code . '?';
                if ($booking->payment && $booking->payment->status === \App\Enums\PaymentStatus::PAID) {
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
    <div class="card">
        <span class="section-kicker">Thanh toán</span>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            @if ($booking->status === \App\Enums\BookingStatus::PENDING)
                Đơn đang chờ xác nhận — bạn có thể thanh toán ngay, không cần chờ khách sạn duyệt đơn trước.
            @else
                Đơn đã được xác nhận. Chọn một trong các hình thức thanh toán bên dưới ("Ví điện tử / Thẻ ngân hàng" chuyển sang cổng VNPay sandbox thật; các hình thức còn lại là demo/thủ công).
            @endif
        </p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                <div class="mb-3 flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-primary-light text-primary dark:bg-primary/15">▦</span>
                    Chuyển khoản ngân hàng
                </div>
                <div class="rounded-lg bg-slate-50 p-3 text-xs leading-relaxed dark:bg-slate-800">
                    Nội dung chuyển khoản: <strong>{{ $booking->booking_code }}</strong>
                </div>
                <form method="POST" action="{{ route('customer.bookings.pay-bank-transfer', $booking->id) }}" class="mt-3"
                    onsubmit="return confirm('Xác nhận bạn đã chuyển khoản cho đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn-outline w-full">Tôi đã chuyển khoản</button>
                </form>
            </div>

            <div class="flex flex-col gap-3">
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="mb-2 flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-primary-light text-primary dark:bg-primary/15">💳</span>
                        Ví điện tử / Thẻ ngân hàng (VNPay)
                    </div>
                    <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">Chuyển sang cổng thanh toán VNPay (môi trường sandbox) để thanh toán bằng thẻ ATM/thẻ quốc tế/QR.</p>
                    <form method="POST" action="{{ route('customer.bookings.pay-online', $booking->id) }}">
                        @csrf
                        <button type="submit" class="btn-primary w-full">Thanh toán qua VNPay</button>
                    </form>
                </div>

                @if ($booking->canPayDeposit())
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                        <div class="mb-2 flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-accent-light text-accent-dark dark:bg-accent/15">🏦</span>
                            Đặt cọc 30%
                        </div>
                        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">Đặt cọc {{ number_format($booking->depositAmount(), 0, ',', '.') }}đ, trả {{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ còn lại bằng tiền mặt khi nhận phòng.</p>
                        <form method="POST" action="{{ route('customer.bookings.pay-deposit', $booking->id) }}"
                            onsubmit="return confirm('Đặt cọc {{ number_format($booking->depositAmount(), 0, ',', '.') }}đ (30%, mô phỏng). Còn lại {{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ trả bằng tiền mặt khi nhận phòng. Tiếp tục?');">
                            @csrf
                            <button type="submit" class="btn-outline w-full">Đặt cọc 30%</button>
                        </form>
                    </div>
                @endif

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
                    <div class="mb-1 flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300">🏨</span>
                        Thanh toán tại khách sạn
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Mặc định — thanh toán bằng tiền mặt khi nhận phòng, không cần thao tác gì thêm.</p>
                </div>
            </div>
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
@endsection
