@extends('layouts.app')

@section('title', 'Yêu cầu trả phòng muộn · Homi')
@section('banner_tag', 'Đơn ' . $booking->booking_code)
@section('banner_title', 'Yêu cầu trả phòng muộn')
@section('banner_subtitle', 'Chọn số giờ bạn muốn trả phòng trễ, khách sạn sẽ kiểm tra tình trạng phòng và phản hồi.')

@section('content')

@php
    $hotel = \App\Models\HotelInfo::instance();
    $standardTime = substr($hotel->check_out_time ?? '12:00:00', 0, 5);
    $minHours = \App\Services\LateCheckoutRequestService::MIN_HOURS_BEFORE_STANDARD_CHECKOUT;
    $autoApproveMaxHours = \App\Services\LateCheckoutRequestService::AUTO_APPROVE_MAX_HOURS;
    $lastNightTotal = \App\Services\LateCheckoutRequestService::lastNightTotal($booking);

    $hourOptions = collect(range(1, 6))->map(function (int $h) use ($standardTime, $autoApproveMaxHours, $lastNightTotal) {
        $checkoutTime = \Carbon\Carbon::createFromFormat('H:i', $standardTime)->addHours($h)->format('H:i');

        return [
            'hours' => $h,
            'time'  => $checkoutTime,
            'fee'   => \App\Services\LateCheckoutRequestService::calculateFee($h, $checkoutTime >= '18:00', $lastNightTotal),
            'auto'  => $h <= $autoApproveMaxHours,
        ];
    });
@endphp

<div class="card auth-card" style="max-width: 640px; margin: 0 auto;">
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="info-list mb-5">
        <div class="info-item">
            <span class="label">Ngày trả phòng</span>
            <span class="value">{{ $booking->check_out->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Giờ trả phòng chuẩn</span>
            <span class="value">{{ $standardTime }}</span>
        </div>
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
        Vui lòng gửi yêu cầu trước giờ trả phòng chuẩn ít nhất {{ $minHours }} giờ để khách sạn kịp kiểm tra tình trạng phòng.
    </p>

    <div class="alert alert-warning mb-4 text-xs leading-relaxed">
        ⚠️ Muốn trả phòng trễ <strong>trên 6 giờ</strong>? Phụ phí sẽ bằng 100% giá phòng (như đặt thêm 1 đêm)
        — bạn nên cân nhắc <strong>gia hạn thêm hẳn 1 ngày</strong> thay vì trả phòng muộn, sẽ tiết kiệm hơn
        và chủ động hơn về phòng ốc. Vui lòng <strong>xuống trực tiếp trao đổi với nhân viên ở quầy lễ tân</strong>
        để được tư vấn phương án phù hợp nhất (không gửi được qua form này).
    </div>

    <form method="POST" action="{{ route('customer.bookings.late-checkout.store', $booking->id) }}" class="form-grid">
        @csrf

        <div class="form-group">
            <label for="hours_late">Bạn muốn trả phòng trễ bao lâu?</label>
            <select id="hours_late" name="hours_late" required>
                <option value="">-- Chọn số giờ --</option>
                @foreach ($hourOptions as $opt)
                    <option
                        value="{{ $opt['hours'] }}"
                        data-fee="{{ (int) $opt['fee'] }}"
                        data-auto="{{ $opt['auto'] ? '1' : '0' }}"
                        @selected(old('hours_late') == $opt['hours'])
                    >Trễ {{ $opt['hours'] }} giờ (khoảng {{ $opt['time'] }})</option>
                @endforeach
            </select>
            <p id="hours-late-fee-preview" class="text-xs text-slate-500 dark:text-slate-400 mt-1.5"></p>
        </div>

        <div class="form-group">
            <label for="reason">Lý do (không bắt buộc)</label>
            <textarea id="reason" name="reason" rows="3" placeholder="VD: chuyến bay về muộn...">{{ old('reason') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Gửi yêu cầu trả phòng muộn</button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('customer.bookings.show', $booking->id) }}">← Quay lại đơn đặt phòng</a>
    </div>
</div>

<script>
    (function () {
        var select = document.getElementById('hours_late');
        var preview = document.getElementById('hours-late-fee-preview');
        if (! select || ! preview) return;

        function render() {
            var opt = select.options[select.selectedIndex];
            if (! opt || ! opt.value) {
                preview.textContent = '';
                return;
            }

            var fee = Number(opt.dataset.fee).toLocaleString('vi-VN') + 'đ';
            var note = opt.dataset.auto === '1' ? 'tự động duyệt ngay' : 'cần khách sạn duyệt';
            preview.textContent = 'Phụ phí dự kiến: ' + fee + ' — ' + note + '.';
        }

        select.addEventListener('change', render);
        render();
    })();
</script>
@endsection
