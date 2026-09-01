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

    $hourOptions = collect(range(1, 6))->map(function (int $h) use ($standardTime, $autoApproveMaxHours) {
        $checkoutTime = \Carbon\Carbon::createFromFormat('H:i', $standardTime)->addHours($h)->format('H:i');

        return [
            'hours'   => $h,
            'time'    => $checkoutTime,
            'after18' => $checkoutTime >= '18:00',
            'auto'    => $h <= $autoApproveMaxHours,
        ];
    });

    // Giá đêm cuối/phòng của từng dòng (mọi phòng cùng dòng đơn cùng giá) —
    // phí trả phòng muộn là % giá phòng nên PHẢI tính lại theo đúng PHÒNG
    // VẬT LÝ khách chọn ở dưới (khác phí nhận phòng sớm, cố định không phụ
    // thuộc số phòng), xem LateCheckoutRequestService::lastNightTotal(). JS
    // dùng data-nightly để tính lại phí ngay khi khách tick/bỏ tick phòng,
    // khớp với calculateFee() ở server (server vẫn là nguồn tính phí thật
    // khi submit).
    $nightlyRateByItemId = $booking->bookingItems->mapWithKeys(function ($item) {
        $breakdown = $item->price_breakdown ?? [];
        $lastNight = $breakdown !== [] ? (end($breakdown)['nightly_total'] ?? $item->price_per_night) : $item->price_per_night;

        return [$item->id => (float) $lastNight];
    });

    // Chỉ phòng ĐANG THỰC SỰ LƯU TRÚ (đã check-in, chưa check-out) mới có ý
    // nghĩa để xin trả muộn — đơn nhiều phòng có thể check-in từng phần nên
    // không phải mọi phòng trong đơn đều chắc chắn đã có mặt (xem
    // Booking::inHouseBookingItemRooms()).
    $inHouseRooms = $booking->inHouseBookingItemRooms();
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
            <label>Chọn phòng muốn trả muộn</label>
            @if ($inHouseRooms->isEmpty())
                <p class="text-xs text-red-500">Chưa có phòng nào trong đơn được xác nhận đang lưu trú — vui lòng liên hệ quầy lễ tân.</p>
            @else
                <div class="checkbox-grid">
                    @foreach ($inHouseRooms as $bir)
                        <label class="checkbox-item">
                            <input type="checkbox" name="room_selections[]" value="{{ $bir->id }}" class="room-checkbox"
                                data-nightly="{{ (int) ($nightlyRateByItemId[$bir->booking_item_id] ?? 0) }}"
                                @checked(in_array((string) $bir->id, old('room_selections', $inHouseRooms->pluck('id')->all())))>
                            Phòng {{ $bir->room->room_number }} ({{ $bir->bookingItem->roomType->name ?? 'Phòng' }})
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-slate-400 mt-1.5">Mặc định chọn hết — phụ phí sẽ tính theo đúng phòng bạn chọn ở đây. Chỉ hiện phòng đang thực sự lưu trú.</p>
            @endif
        </div>

        <div class="form-group">
            <label for="hours_late">Bạn muốn trả phòng trễ bao lâu?</label>
            <select id="hours_late" name="hours_late" required>
                <option value="">-- Chọn số giờ --</option>
                @foreach ($hourOptions as $opt)
                    <option
                        value="{{ $opt['hours'] }}"
                        data-after18="{{ $opt['after18'] ? '1' : '0' }}"
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
        var roomCheckboxes = document.querySelectorAll('.room-checkbox');
        if (! select || ! preview) return;

        // Mirror đúng LateCheckoutRequestService::calculateFee() — chỉ để
        // xem trước, server vẫn là nơi tính phí thật khi submit.
        function calculateFee(hours, isAfter18, lastNightTotal) {
            if (isAfter18 || hours > 5) return Math.round(lastNightTotal);
            return Math.round(lastNightTotal * hours * 0.10);
        }

        function selectedLastNightTotal() {
            var total = 0;
            roomCheckboxes.forEach(function (checkbox) {
                if (checkbox.checked) total += Number(checkbox.dataset.nightly) || 0;
            });
            return total;
        }

        function render() {
            var opt = select.options[select.selectedIndex];
            if (! opt || ! opt.value) {
                preview.textContent = '';
                return;
            }

            var fee = calculateFee(Number(opt.value), opt.dataset.after18 === '1', selectedLastNightTotal());
            var note = opt.dataset.auto === '1' ? 'tự động duyệt ngay' : 'cần khách sạn duyệt';
            preview.textContent = 'Phụ phí dự kiến: ' + fee.toLocaleString('vi-VN') + 'đ — ' + note + '.';
        }

        select.addEventListener('change', render);
        roomCheckboxes.forEach(function (checkbox) { checkbox.addEventListener('change', render); });
        render();
    })();
</script>
@endsection
