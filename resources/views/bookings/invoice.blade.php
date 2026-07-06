@extends('layouts.print')

@php
    $hotel = \App\Models\HotelInfo::instance();
@endphp

@section('title', 'Hóa đơn ' . $booking->booking_code . ' · Homi')

@section('content')
<div class="mb-4 flex items-center justify-between print:hidden">
    <a href="{{ $backRoute }}" class="btn-outline btn-sm">← Quay lại</a>
    <button onclick="window.print()" class="btn-primary btn-sm">🖨 In hóa đơn</button>
</div>

<div class="card" id="invoice-document">
    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4 dark:border-slate-800">
        <div>
            <div class="font-heading text-xl font-extrabold text-primary">{{ $hotel->name }}</div>
            <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $hotel->address }}</div>
            @if ($hotel->phone)
                <div class="text-sm text-slate-500 dark:text-slate-400">ĐT: {{ $hotel->phone }}</div>
            @endif
            @if ($hotel->email)
                <div class="text-sm text-slate-500 dark:text-slate-400">Email: {{ $hotel->email }}</div>
            @endif
        </div>
        <div class="text-right">
            <div class="font-heading text-lg font-extrabold text-slate-900 dark:text-white">HÓA ĐƠN NỘI BỘ</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Số: INV-{{ $booking->booking_code }}</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Ngày: {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400 italic">
        Đây là biên nhận/hóa đơn nội bộ dùng để đối soát, không phải hóa đơn điện tử hợp lệ theo quy định thuế.
    </p>

    <div class="info-list mt-4">
        <div class="info-item">
            <span class="label">Mã đơn</span>
            <span class="value">{{ $booking->booking_code }}</span>
        </div>
        <div class="info-item">
            <span class="label">Khách hàng</span>
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
        <div class="info-item">
            <span class="label">Nhận phòng</span>
            <span class="value">{{ $booking->check_in->format('d/m/Y') }}</span>
        </div>
        <div class="info-item">
            <span class="label">Trả phòng</span>
            <span class="value">{{ $booking->check_out->format('d/m/Y') }}</span>
        </div>
    </div>

    <span class="section-kicker mt-5 block">Phòng</span>
    <div class="table-wrapper mt-2.5">
        <table>
            <thead>
                <tr>
                    <th>Loại phòng</th>
                    <th>SL</th>
                    <th>Giá/đêm</th>
                    <th>Số đêm</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($booking->bookingItems as $item)
                    <tr>
                        <td>{{ $item->roomType->name ?? '—' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price_per_night, 0, ',', '.') }}đ</td>
                        <td>{{ $item->nights }}</td>
                        <td>{{ number_format($item->subtotal + $item->child_surcharge, 0, ',', '.') }}đ</td>
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
                        <th>SL</th>
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
            <span class="label">Tạm tính</span>
            <span class="value">{{ number_format($booking->total_amount + $booking->discount_amount, 0, ',', '.') }}đ</span>
        </div>
        @if ($booking->discount_amount > 0)
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
            <span class="label">Tổng cộng</span>
            <span class="value text-lg text-primary">{{ number_format($booking->total_amount, 0, ',', '.') }}đ</span>
        </div>
    </div>

    <span class="section-kicker mt-5 block">Thanh toán</span>
    <div class="info-list mt-2.5">
        @if ($booking->payment)
            <div class="info-item">
                <span class="label">Trạng thái</span>
                <span class="value"><span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span></span>
            </div>
            <div class="info-item">
                <span class="label">Phương thức</span>
                <span class="value">{{ $booking->payment->method->label() }}</span>
            </div>
            @if ($booking->payment->isPaid())
                <div class="info-item">
                    <span class="label">Số tiền đã thanh toán</span>
                    <span class="value">{{ number_format($booking->payment->amount, 0, ',', '.') }}đ</span>
                </div>
                @if ($booking->payment->paid_at)
                    <div class="info-item">
                        <span class="label">Thời gian thanh toán</span>
                        <span class="value">{{ $booking->payment->paid_at->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            @elseif ($booking->payment->deposit_paid_at)
                <div class="info-item">
                    <span class="label">Đã đặt cọc</span>
                    <span class="value">{{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ</span>
                </div>
                <div class="info-item">
                    <span class="label">Còn lại</span>
                    <span class="value">{{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ</span>
                </div>
            @else
                <div class="info-item">
                    <span class="label">Còn phải thu</span>
                    <span class="value">{{ number_format($booking->total_amount, 0, ',', '.') }}đ</span>
                </div>
            @endif
        @else
            <div class="info-item">
                <span class="label">Trạng thái</span>
                <span class="value">Chưa có thông tin thanh toán</span>
            </div>
        @endif
    </div>
</div>
@endsection
