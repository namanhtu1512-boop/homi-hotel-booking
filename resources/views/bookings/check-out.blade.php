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
            được duyệt trước sẽ KHÔNG tự động bị tính phí — nếu cần thu, quay lại
            <a href="{{ $backRoute }}">trang chi tiết đơn</a> để thêm phụ phí phát sinh, hoặc hướng dẫn khách gửi
            "Yêu cầu trả phòng muộn" trước khi hết giờ chuẩn để được duyệt và tính phí đúng bậc.
        </p>
    @endif

    <form method="POST" action="{{ $formAction }}" class="mt-4"
        onsubmit="return confirm('Xác nhận đã thu đủ hóa đơn phát sinh (nếu có) và hoàn tất trả phòng cho đơn {{ $booking->booking_code }}?');">
        @csrf
        <button type="submit" class="btn btn-primary btn-block">Xác nhận đã thu & Trả phòng</button>
    </form>
</div>
@endsection
