@extends('layouts.print')

@php
    $hotel = \App\Models\HotelInfo::instance();
    $incidentalInvoice = $booking->incidentalInvoice;
    $incidentalItems = $incidentalInvoice?->items ?? collect();
    $grandTotal = $booking->total_amount + ($incidentalInvoice->total_amount ?? 0);
@endphp

@section('title', 'Hóa đơn ' . $booking->booking_code . ' · Homi')

@section('content')
<div class="mb-4 flex items-center justify-between print:hidden">
    <a href="{{ $backRoute }}" class="btn-outline btn-sm">← Quay lại</a>
    <button onclick="window.print()" class="btn-primary btn-sm">🖨 In hóa đơn</button>
</div>

<div class="card overflow-hidden p-0" id="invoice-document">
    <div class="invoice-header flex flex-wrap items-center justify-between gap-6 bg-primary px-8 py-7 text-white">
        <div class="flex items-center gap-4">
            <div class="invoice-monogram grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-white/15 font-heading text-2xl font-extrabold">
                {{ mb_strtoupper(mb_substr($hotel->name, 0, 1)) }}
            </div>
            <div>
                <div class="font-heading text-xl font-extrabold">{{ $hotel->name }}</div>
                <div class="mt-1 text-sm text-white/80">{{ $hotel->address }}</div>
                <div class="mt-0.5 flex flex-wrap gap-x-4 text-sm text-white/80">
                    @if ($hotel->phone)
                        <span>ĐT: {{ $hotel->phone }}</span>
                    @endif
                    @if ($hotel->email)
                        <span>{{ $hotel->email }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="text-right">
            <div class="font-heading text-2xl font-extrabold tracking-wide">HÓA ĐƠN</div>
            <div class="mt-1 text-sm text-white/80">Số: INV-{{ $booking->booking_code }}</div>
            <div class="text-sm text-white/80">Ngày: {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="px-8 py-7">
        <p class="text-xs text-slate-400 italic">
            Đây là biên nhận/hóa đơn nội bộ dùng để đối soát, không phải hóa đơn điện tử hợp lệ theo quy định thuế.
        </p>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                <span class="section-kicker">Khách hàng</span>
                <div class="mt-2 space-y-1 text-sm">
                    <div class="font-bold text-slate-900 dark:text-white">{{ $booking->customer_name }}</div>
                    <div class="text-slate-500 dark:text-slate-400">{{ $booking->customer_phone }}</div>
                    @if ($booking->customer_email)
                        <div class="text-slate-500 dark:text-slate-400">{{ $booking->customer_email }}</div>
                    @endif
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                <span class="section-kicker">Thông tin đặt phòng</span>
                <div class="mt-2 grid grid-cols-2 gap-y-1.5 text-sm">
                    <div class="text-slate-500 dark:text-slate-400">Mã đơn</div>
                    <div class="text-right font-bold text-slate-900 dark:text-white">{{ $booking->booking_code }}</div>
                    <div class="text-slate-500 dark:text-slate-400">Nhận phòng</div>
                    <div class="text-right font-bold text-slate-900 dark:text-white">{{ $booking->check_in->format('d/m/Y') }}</div>
                    <div class="text-slate-500 dark:text-slate-400">Trả phòng</div>
                    <div class="text-right font-bold text-slate-900 dark:text-white">{{ $booking->check_out->format('d/m/Y') }}</div>
                    <div class="text-slate-500 dark:text-slate-400">Số khách</div>
                    <div class="text-right font-bold text-slate-900 dark:text-white">{{ $booking->adults }} NL{{ $booking->children ? ', ' . $booking->children . ' TE' : '' }}{{ $booking->infants ? ', ' . $booking->infants . ' SS' : '' }}</div>
                </div>
            </div>
        </div>

        <span class="section-kicker mt-6 block">Phòng</span>
        <div class="table-wrapper mt-2.5">
            <table>
                <thead>
                    <tr>
                        <th>Loại phòng</th>
                        <th class="text-center">SL</th>
                        <th class="text-right">Giá/đêm</th>
                        <th class="text-center">Số đêm</th>
                        <th class="text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($booking->bookingItems as $item)
                        <tr>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $item->roomType->name ?? '—' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">{{ number_format($item->price_per_night, 0, ',', '.') }}đ</td>
                            <td class="text-center">{{ $item->nights }}</td>
                            <td class="text-right font-bold">{{ number_format($item->subtotal + $item->child_surcharge, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($booking->serviceItems->isNotEmpty())
            <span class="section-kicker mt-6 block">Dịch vụ thêm</span>
            <div class="table-wrapper mt-2.5">
                <table>
                    <thead>
                        <tr>
                            <th>Dịch vụ</th>
                            <th class="text-center">SL</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($booking->serviceItems as $serviceItem)
                            <tr>
                                <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $serviceItem->service?->name ?? '—' }}</td>
                                <td class="text-center">{{ $serviceItem->quantity }}</td>
                                <td class="text-right">{{ number_format($serviceItem->unit_price, 0, ',', '.') }}đ</td>
                                <td class="text-right font-bold">{{ number_format($serviceItem->subtotal, 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($incidentalItems->isNotEmpty())
            <span class="section-kicker mt-6 block">Hóa đơn phát sinh</span>
            <div class="table-wrapper mt-2.5">
                <table>
                    <thead>
                        <tr>
                            <th>Loại</th>
                            <th>Mô tả</th>
                            <th class="text-right">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incidentalItems as $item)
                            <tr>
                                <td>{{ $item->type === 'service' ? 'Dịch vụ' : 'Phụ phí' }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-right font-bold">{{ number_format($item->amount, 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-2 text-right text-sm text-slate-500 dark:text-slate-400">
                Tổng hóa đơn phát sinh: <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($incidentalInvoice->total_amount, 0, ',', '.') }}đ</span>
                ({{ $incidentalInvoice->isPaid() ? 'đã thanh toán' : 'chưa thanh toán' }})
            </div>
        @endif

        <div class="mt-6 flex justify-end">
            <div class="w-full space-y-2 text-sm sm:max-w-sm">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                    <span>Tạm tính</span>
                    <span>{{ number_format($booking->total_amount + $booking->discount_amount, 0, ',', '.') }}đ</span>
                </div>
                @if ($booking->discount_amount > 0)
                    @forelse ($booking->promotions as $promo)
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                            <span>Giảm giá ({{ $promo->code }})</span>
                            <span class="text-accent-dark">-{{ number_format($promo->pivot->discount_amount, 0, ',', '.') }}đ</span>
                        </div>
                    @empty
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                            <span>Giảm giá {{ $booking->promotion ? '(' . $booking->promotion->code . ')' : '' }}</span>
                            <span class="text-accent-dark">-{{ number_format($booking->discount_amount, 0, ',', '.') }}đ</span>
                        </div>
                    @endforelse
                @endif
                <div class="flex items-center justify-between border-t border-slate-200 pt-2 font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                    <span>Tiền phòng</span>
                    <span>{{ number_format($booking->total_amount, 0, ',', '.') }}đ</span>
                </div>
                @if ($incidentalItems->isNotEmpty())
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span>Hóa đơn phát sinh</span>
                        <span>{{ number_format($incidentalInvoice->total_amount, 0, ',', '.') }}đ</span>
                    </div>
                @endif
                <div class="invoice-total-box mt-1 flex items-center justify-between rounded-xl bg-primary-light px-4 py-3.5 dark:bg-primary/15">
                    <span class="font-heading text-base font-extrabold text-slate-900 dark:text-white">TỔNG CỘNG</span>
                    <span class="font-heading text-xl font-extrabold text-primary">{{ number_format($grandTotal, 0, ',', '.') }}đ</span>
                </div>
            </div>
        </div>

        <span class="section-kicker mt-6 block">Thanh toán</span>
        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
            @if ($booking->payment)
                <div class="grid gap-y-1.5 text-sm sm:grid-cols-2">
                    <div class="flex items-center justify-between gap-2 sm:justify-start">
                        <span class="text-slate-500 dark:text-slate-400">Trạng thái</span>
                        <span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end sm:gap-2">
                        <span class="text-slate-500 dark:text-slate-400">Phương thức</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100">{{ $booking->payment->method->label() }}</span>
                    </div>
                    @if ($booking->payment->surcharge_amount > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Phụ phí phát sinh</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($booking->payment->surcharge_amount, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end sm:gap-2">
                            <span class="text-slate-500 dark:text-slate-400">Lý do phụ phí</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ $booking->payment->surcharge_note }}</span>
                        </div>
                    @endif
                    @if ($booking->payment->isPaid())
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Số tiền đã thanh toán</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($booking->payment->amount, 0, ',', '.') }}đ</span>
                        </div>
                        @if ($booking->payment->paid_at)
                            <div class="flex items-center justify-between sm:justify-end sm:gap-2">
                                <span class="text-slate-500 dark:text-slate-400">Thời gian thanh toán</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $booking->payment->paid_at->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                    @elseif ($booking->payment->deposit_paid_at)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Đã đặt cọc</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end sm:gap-2">
                            <span class="text-slate-500 dark:text-slate-400">Còn lại</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ</span>
                        </div>
                    @else
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Còn phải thu</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($booking->total_amount, 0, ',', '.') }}đ</span>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-sm text-slate-500 dark:text-slate-400">Chưa có thông tin thanh toán</div>
            @endif
        </div>

        <div class="invoice-signatures mt-10 grid grid-cols-2 gap-4 border-t border-slate-200 pt-8 text-center text-sm dark:border-slate-800">
            <div>
                <div class="font-bold text-slate-900 dark:text-white">Khách hàng</div>
                <div class="text-xs text-slate-400">(Ký, ghi rõ họ tên)</div>
                <div class="mt-16"></div>
            </div>
            <div>
                <div class="font-bold text-slate-900 dark:text-white">Nhân viên lập hóa đơn</div>
                <div class="text-xs text-slate-400">(Ký, ghi rõ họ tên)</div>
                <div class="mt-16"></div>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            Cảm ơn quý khách đã tin tưởng và sử dụng dịch vụ tại {{ $hotel->name }}!
        </p>
    </div>
</div>
@endsection
