{{--
    Lịch sử đổi phòng đã DUYỆT — hiện tiền phòng TRƯỚC/SAU từng lần đổi, dùng
    chung cho cả 3 trang chi tiết đơn (customer/staff/admin). Cần truyền vào
    $booking (đã eager-load roomChangeRequests.currentRoomType/requestedRoomType)
    + $roomOnlyTotal (tiền phòng HIỆN TẠI của đơn, đã tính sẵn ở trang gọi).

    Chỉ tính các bản ghi có price_delta (lưu từ RoomChangeRequestService::approve()
    — bản ghi cũ trước khi có cột này sẽ có price_delta = null, bỏ qua vì
    không đủ dữ liệu để tính đúng before/after).

    $wrapCard (mặc định true) — trang customer có nhiều .card riêng biệt nên
    cần tự bọc 1 .card mới; trang staff/admin gộp toàn bộ nội dung trong 1
    .card lớn duy nhất nên truyền false để chỉ render phần section-kicker +
    info-list bên trong, không lồng thêm .card nữa.
--}}
@php
    $wrapCard = $wrapCard ?? true;
    $approvedRoomChanges = $booking->roomChangeRequests
        ->where('status', 'approved')
        ->whereNotNull('price_delta')
        ->sortBy('handled_at')
        ->values();
@endphp
@if ($approvedRoomChanges->isNotEmpty())
    @php
        $roomChangeRunningTotal = $roomOnlyTotal - (float) $approvedRoomChanges->sum('price_delta');
    @endphp
    @if ($wrapCard) <div class="card"> @endif
        <span class="section-kicker{{ $wrapCard ? '' : ' mt-4 block' }}">Lịch sử đổi phòng</span>
        <div class="info-list mt-2.5">
            @foreach ($approvedRoomChanges as $rcr)
                @php
                    $rcrBefore = $roomChangeRunningTotal;
                    $rcrAfter = $rcrBefore + (float) $rcr->price_delta;
                    $roomChangeRunningTotal = $rcrAfter;

                    $rcrParts = [];
                    if ($rcr->requested_room_type_id) {
                        $rcrParts[] = ($rcr->quantity ?? '') . ' phòng ' . ($rcr->currentRoomType?->name ?? 'phòng cũ') . ' → ' . ($rcr->requestedRoomType?->name ?? 'phòng mới');
                    }
                    if ($rcr->requested_check_in) {
                        $rcrParts[] = 'đổi ngày ở sang ' . $rcr->requested_check_in->format('d/m/Y') . ' - ' . $rcr->requested_check_out->format('d/m/Y');
                    }
                @endphp
                <div class="info-item">
                    <span class="label">
                        {{ implode(', ', $rcrParts) ?: 'Đổi phòng' }}
                        <div class="text-xs font-normal text-slate-500 dark:text-slate-400">
                            Duyệt lúc {{ $rcr->handled_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                        </div>
                    </span>
                    <span class="value">
                        {{ number_format($rcrBefore, 0, ',', '.') }}đ → {{ number_format($rcrAfter, 0, ',', '.') }}đ
                        <span class="badge {{ $rcr->price_delta > 0 ? 'badge-orange' : 'badge-green' }}">
                            {{ $rcr->price_delta > 0 ? '+' : '' }}{{ number_format($rcr->price_delta, 0, ',', '.') }}đ
                        </span>
                    </span>
                </div>
            @endforeach
        </div>
    @if ($wrapCard) </div> @endif
@endif
