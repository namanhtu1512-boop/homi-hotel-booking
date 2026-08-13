@extends($layout)

@section('title', 'Trả phòng đơn ' . $booking->booking_code)
@section('page_title', 'Trả phòng đơn ' . $booking->booking_code)
@section('page_subtitle', 'Kiểm tra phòng, xác nhận toàn bộ hóa đơn phát sinh trước khi hoàn tất trả phòng.')

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
        $invoice = $booking->incidentalInvoice;
        $items = $invoice?->items ?? collect();
    @endphp

    <span class="section-kicker mt-3 block">Hóa đơn phát sinh</span>

    @if ($items->isEmpty())
        <div class="empty-box mt-2">Không có khoản phát sinh nào trong lúc lưu trú.</div>
    @else
        <div class="table-wrapper mt-2.5">
            <table>
                <thead>
                    <tr>
                        <th>Loại</th>
                        <th>Mô tả</th>
                        <th>Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->type === 'service' ? 'Dịch vụ' : 'Phụ phí' }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ number_format($item->amount, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Tổng cộng</strong></td>
                        <td><strong>{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($invoice->isOpen())
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Chưa thu — vui lòng thu đủ số tiền trên trước khi bấm xác nhận trả phòng.
            </p>
        @else
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Đã thanh toán lúc {{ $invoice->paid_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}.
            </p>
        @endif
    @endif

    @php
        $hotel = \App\Models\HotelInfo::instance();
    @endphp
    @if ($hotel->check_out_time && $booking->isCheckOutDateToday())
        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            Giờ trả phòng chuẩn: {{ substr($hotel->check_out_time, 0, 5) }}. Trả phòng muộn không có yêu cầu
            được duyệt trước sẽ KHÔNG tự động bị tính phí — nếu cần thu, dùng mục "Thêm phụ phí phát sinh" bên
            dưới, hoặc hướng dẫn khách gửi "Yêu cầu trả phòng muộn" trước khi hết giờ chuẩn để được duyệt và tính
            phí đúng bậc.
        </p>
    @endif

    @if ($booking->status === \App\Enums\BookingStatus::CHECKED_IN)
        <span class="section-kicker mt-4 block">Thêm phụ phí phát sinh</span>
        <div class="mt-2 flex flex-col gap-3">
            <form method="POST" action="{{ $serviceStoreRoute }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                @csrf
                @include('partials.surcharge-item-select', ['items' => $activeServices, 'hiddenField' => 'service_id', 'placeholder' => 'Gõ để tìm dịch vụ...'])
                <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="20" value="1" title="Số lượng">
                <input type="number" name="amount" class="input surcharge-amount" style="width:150px;" min="1" step="1000" placeholder="Số tiền (nếu chưa có giá cố định)">
                <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Ghi chú (không bắt buộc)">
                <button type="submit" class="btn btn-outline btn-sm">🔵 Thêm dịch vụ phát sinh</button>
            </form>

            <form method="POST" action="{{ $surchargeStoreRoute }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                @csrf
                @include('partials.surcharge-item-select', ['items' => $damageItems, 'hiddenField' => 'surcharge_item_id', 'placeholder' => 'Gõ để tìm đồ hỏng/mất...', 'notePrefix' => 'Bồi thường: '])
                <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="99" value="1" title="Số lượng">
                <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do (VD: hư hỏng đồ...)" required>
                <button type="submit" class="btn btn-outline btn-sm">🔴 Thêm phụ phí hỏng/mất đồ</button>
            </form>

            <form method="POST" action="{{ $surchargeStoreRoute }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                @csrf
                @include('partials.surcharge-item-select', ['items' => $violationItems, 'hiddenField' => 'surcharge_item_id', 'placeholder' => 'Gõ để tìm vi phạm...', 'notePrefix' => 'Vi phạm: '])
                <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="99" value="1" title="Số lượng">
                <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do" required>
                <button type="submit" class="btn btn-outline btn-sm">🟠 Thêm phụ phí vi phạm</button>
            </form>

            <form method="POST" action="{{ $surchargeStoreRoute }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                @csrf
                @include('partials.surcharge-item-select', ['items' => $cleaningItems, 'hiddenField' => 'surcharge_item_id', 'placeholder' => 'Gõ để tìm khoản vệ sinh...', 'notePrefix' => 'Vệ sinh đặc biệt: '])
                <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="99" value="1" title="Số lượng">
                <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do" required>
                <button type="submit" class="btn btn-outline btn-sm">🟡 Thêm phụ phí vệ sinh đặc biệt</button>
            </form>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="mt-4"
        onsubmit="return confirm('Xác nhận đã thu đủ hóa đơn phát sinh (nếu có) và hoàn tất trả phòng cho đơn {{ $booking->booking_code }}?');">
        @csrf
        <button type="submit" class="btn btn-primary btn-block">Xác nhận đã thu & Trả phòng</button>
    </form>
</div>
@endsection
