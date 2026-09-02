@extends($layout)

@section('title', 'Trả phòng đơn ' . $booking->booking_code)
@section('page_title', 'Trả phòng đơn ' . $booking->booking_code)
@section('page_subtitle', 'Mỗi phòng được trả và quyết toán tiền RIÊNG — chọn phòng nào sẵn sàng trả ngay bây giờ.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ $backRoute }}" class="btn btn-outline">Quay lại</a>
    </div>

    @if ($booking->isEarlyCheckoutToday())
        <div class="alert alert-warning">
            ⚠ Khách trả phòng SỚM hơn ngày đã đặt — còn {{ $booking->nightsRemainingForEarlyCheckout() }} đêm
            chưa sử dụng (ngày trả phòng đã đặt: {{ $booking->check_out->format('d/m/Y') }}).
        </div>
    @endif

    @php
        $hotel = \App\Models\HotelInfo::instance();
    @endphp
    @if ($hotel->check_out_time && $booking->isCheckOutDateToday())
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Giờ trả phòng chuẩn: {{ substr($hotel->check_out_time, 0, 5) }}. Nếu khách chưa có "Yêu cầu trả phòng
            muộn" được duyệt trước và trả phòng NGAY BÂY GIỜ (sau giờ chuẩn này), hệ thống sẽ TỰ ĐỘNG cộng phụ phí
            trả phòng muộn theo bậc vào phòng đầu tiên được trả trong đợt này.
        </p>
    @endif

    @if ($pendingRooms->isEmpty())
        <div class="empty-box mt-3">Không còn phòng nào đang lưu trú cần trả.</div>
    @else
        @php
            $groupAmountDue = $pendingRooms->sum(fn ($row) => max(0, $row['preview']['amount_due']));
        @endphp

        @if ($booking->status === \App\Enums\BookingStatus::CHECKED_IN)
            <div class="card mt-4 mb-0" style="display:flex; flex-direction:column; gap:12px;">
                <div class="section-kicker">Phát sinh khi kiểm tra phòng trước khi trả</div>

                <form method="POST" action="{{ $surchargeFormAction }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                    @csrf
                    @include('partials.surcharge-item-select', ['items' => $damageItems, 'hiddenField' => 'surcharge_item_id', 'placeholder' => 'Gõ để tìm đồ hỏng/mất...', 'notePrefix' => 'Bồi thường: '])
                    <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="99" value="1" title="Số lượng">
                    <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                    <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do (VD: hư hỏng đồ...)" required>
                    @include('partials._room-select')
                    <button type="submit" class="btn btn-outline btn-sm">🔴 Thêm phụ phí hỏng/mất đồ</button>
                </form>

                <form method="POST" action="{{ $surchargeFormAction }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                    @csrf
                    @include('partials.surcharge-item-select', ['items' => $violationItems, 'hiddenField' => 'surcharge_item_id', 'placeholder' => 'Gõ để tìm vi phạm...', 'notePrefix' => 'Vi phạm: '])
                    <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="99" value="1" title="Số lượng">
                    <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                    <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do" required>
                    @include('partials._room-select')
                    <button type="submit" class="btn btn-outline btn-sm">🟠 Thêm phụ phí vi phạm</button>
                </form>

                <form method="POST" action="{{ $surchargeFormAction }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                    @csrf
                    @include('partials.surcharge-item-select', ['items' => $cleaningItems, 'hiddenField' => 'surcharge_item_id', 'placeholder' => 'Gõ để tìm khoản vệ sinh...', 'notePrefix' => 'Vệ sinh đặc biệt: '])
                    <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="99" value="1" title="Số lượng">
                    <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                    <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do" required>
                    @include('partials._room-select')
                    <button type="submit" class="btn btn-outline btn-sm">🟡 Thêm phụ phí vệ sinh đặc biệt</button>
                </form>

                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0">Thêm xong khoản nào, số tiền "Còn phải thu" bên dưới sẽ tự cập nhật theo phòng đã chọn.</p>
            </div>
        @endif

        @if ($pendingRooms->count() > 1)
            <div class="card mt-4 mb-0">
                <div class="info-list">
                    <div class="info-item">
                        <span class="label">Tổng tiền cần thu của cả đơn ({{ $pendingRooms->count() }} phòng)</span>
                        <span class="value font-bold">{{ number_format($groupAmountDue, 0, ',', '.') }}đ</span>
                    </div>
                </div>
                <div class="form-group mt-3 mb-0">
                    <label for="checkoutRoomFilter">Lọc theo phòng (chỉ để xem — không ảnh hưởng phòng đã chọn trả)</label>
                    <select id="checkoutRoomFilter" class="input" onchange="homiFilterCheckoutRoom(this.value)">
                        <option value="">Tất cả phòng</option>
                        @foreach ($pendingRooms as $row)
                            @php [$bir, $preview] = [$row['room'], $row['preview']]; @endphp
                            <option value="{{ $bir->id }}">
                                Phòng {{ $bir->room->room_number ?? '—' }} — còn phải thu {{ number_format(max(0, $preview['amount_due']), 0, ',', '.') }}đ
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" class="mt-4"
            onsubmit="return confirm('Xác nhận đã thu tiền và trả phòng cho các phòng đã chọn?');">
            @csrf

            <div class="table-wrapper">
                <table id="checkoutRoomsTable">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Phòng</th>
                            <th class="text-right">Tiền phòng</th>
                            <th class="text-right">Dịch vụ/phụ phí riêng phòng</th>
                            <th class="text-right">Trừ cọc/trả trước đã phân bổ</th>
                            <th class="text-right">Còn phải thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingRooms as $row)
                            @php [$bir, $preview] = [$row['room'], $row['preview']]; @endphp
                            <tr data-room-id="{{ $bir->id }}">
                                <td><input type="checkbox" name="rooms[]" value="{{ $bir->id }}" checked></td>
                                <td class="font-semibold text-slate-800 dark:text-slate-100">
                                    Phòng {{ $bir->room->room_number ?? '—' }}
                                    <div class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ $bir->bookingItem->roomType->name ?? '—' }}</div>
                                </td>
                                <td class="text-right">{{ number_format($preview['room_charge'], 0, ',', '.') }}đ</td>
                                <td class="text-right">{{ number_format($preview['service_charge'], 0, ',', '.') }}đ</td>
                                <td class="text-right">-{{ number_format($preview['deposit_credit'], 0, ',', '.') }}đ</td>
                                <td class="text-right font-bold">{{ number_format(max(0, $preview['amount_due']), 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <script>
                function homiFilterCheckoutRoom(roomId) {
                    document.querySelectorAll('#checkoutRoomsTable tbody tr').forEach(function (tr) {
                        tr.style.display = (!roomId || tr.dataset.roomId === roomId) ? '' : 'none';
                    });
                }
            </script>

            <div class="form-group mt-3">
                <label for="method">Hình thức thu tiền</label>
                <select id="method" name="method" class="input">
                    <option value="cash">Tiền mặt</option>
                    <option value="bank_transfer">Chuyển khoản</option>
                    <option value="other">Khác</option>
                </select>
            </div>

            <div class="form-group">
                <label for="note">Ghi chú (không bắt buộc)</label>
                <input id="note" type="text" name="note" class="input" placeholder="VD: đã kiểm tra phòng, không hư hỏng...">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Xác nhận đã thu & Trả phòng (các phòng đã tick)</button>
        </form>
    @endif
</div>
@endsection
