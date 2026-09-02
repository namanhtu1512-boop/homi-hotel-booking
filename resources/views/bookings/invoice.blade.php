@extends('layouts.print')

@php
    $hotel = \App\Models\HotelInfo::instance();
    $incidentalInvoice = $booking->incidentalInvoice;
    $room = $room ?? null;
    $settlement = $room?->settlement;

    if ($room) {
        // Chế độ hóa đơn RIÊNG 1 PHÒNG (in lúc check-in phòng đó, hoặc xem
        // lại sau khi phòng đã trả) — chỉ hiện dòng phòng này + dịch vụ/phụ
        // phí gắn đúng phòng này (cộng cả khoản CHƯA gắn phòng nào nếu đơn
        // chỉ có 1 phòng duy nhất — xem BookingService::resolveBookingItemRoomId()).
        $roomItems = collect([$room->bookingItem]);
        $onlyRoomInBooking = $booking->totalRoomsRequired() === 1;
        $serviceItems = $booking->serviceItems->filter(
            fn ($s) => $s->booking_item_room_id === $room->id || ($onlyRoomInBooking && $s->booking_item_room_id === null)
        );
        $incidentalItems = collect($incidentalInvoice?->items ?? [])->filter(
            fn ($i) => $i->booking_item_room_id === $room->id || ($onlyRoomInBooking && $i->booking_item_room_id === null)
        );
    } else {
        $roomItems = $booking->bookingItems;
        $serviceItems = $booking->serviceItems;
        $incidentalItems = $incidentalInvoice?->items ?? collect();
    }

    $grandTotal = $room
        ? ($settlement?->room_charge ?? 0) + ($settlement?->service_charge ?? $serviceItems->sum('subtotal') + $incidentalItems->sum('amount'))
        : $booking->total_amount + ($incidentalInvoice->total_amount ?? 0);

    // bookingItem->subtotal/child_surcharge/extra_bed_surcharge là giá TRƯỚC
    // giảm giá (discount_amount trừ riêng ở cấp đơn) — tách riêng từng phần
    // (giá phòng gốc, phụ thu trẻ em, phụ thu giường phụ, giảm giá) của
    // phòng này ra để hiện rõ trên hóa đơn từng phòng, thay vì gộp hết vào 1
    // con số "Tiền phòng" khiến không khớp với "Giá/đêm × Số đêm" ở bảng
    // trên (xem BookingService::computeRoomSettlementAmounts()).
    $roomBaseCharge = $room ? round((float) $room->bookingItem->subtotal / max(1, $room->bookingItem->quantity)) : 0;
    $roomChildSurcharge = $room ? round((float) $room->bookingItem->child_surcharge / max(1, $room->bookingItem->quantity)) : 0;
    $roomExtraBedSurcharge = $room ? round((float) $room->bookingItem->extra_bed_surcharge / max(1, $room->bookingItem->quantity)) : 0;
    $discountShare = $room ? round((float) $booking->discount_amount / max(1, $booking->totalRoomsRequired())) : 0;

    // Hóa đơn RIÊNG 1 phòng thì khỏi cần cột "Phòng" (đã lọc đúng phòng đó
    // ở trên) — chỉ hiện khi xem hóa đơn CẢ ĐƠN và đơn có từ 2 phòng trở
    // lên, nếu không dịch vụ/phụ phí gắn phòng cụ thể sẽ không rõ của
    // phòng nào khi đơn có nhiều phòng cùng loại (VD 2 Phòng Standard).
    $showRoomColumn = ! $room && $booking->totalRoomsRequired() > 1;
@endphp

@section('title', 'Hóa đơn ' . $booking->booking_code . ($room ? ' — Phòng ' . ($room->room->room_number ?? '') : '') . ' · Homi')

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
            <div class="text-sm text-white/80">Ngày xuất hóa đơn: {{ now('Asia/Ho_Chi_Minh')->format('d/m/Y') }}</div>
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
                    @foreach ($roomItems as $item)
                        <tr>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">
                                {{ $item->roomType->name ?? '—' }}
                                @if ($room)
                                    <div class="text-xs font-normal text-slate-500 dark:text-slate-400">Phòng {{ $room->room->room_number ?? '—' }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $room ? 1 : $item->quantity }}</td>
                            <td class="text-right">{{ number_format($item->price_per_night, 0, ',', '.') }}đ</td>
                            <td class="text-center">{{ $item->nights }}</td>
                            <td class="text-right font-bold">
                                @if ($room)
                                    {{ number_format($settlement->room_charge ?? $roomPreview['room_charge'], 0, ',', '.') }}đ
                                @else
                                    {{ number_format($item->subtotal + $item->child_surcharge + $item->extra_bed_surcharge, 0, ',', '.') }}đ
                                    @if ($item->child_surcharge > 0)
                                        <div class="text-xs font-normal text-slate-500 dark:text-slate-400">(gồm {{ number_format($item->child_surcharge, 0, ',', '.') }}đ phụ thu trẻ em)</div>
                                    @endif
                                    @if ($item->extra_bed_surcharge > 0)
                                        <div class="text-xs font-normal text-slate-500 dark:text-slate-400">(gồm {{ number_format($item->extra_bed_surcharge, 0, ',', '.') }}đ phụ thu giường phụ)</div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($serviceItems->isNotEmpty())
            <span class="section-kicker mt-6 block">Dịch vụ thêm</span>
            <div class="table-wrapper mt-2.5">
                <table>
                    <thead>
                        <tr>
                            <th>Dịch vụ</th>
                            @if ($showRoomColumn)
                                <th>Phòng</th>
                            @endif
                            <th class="text-center">SL</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($serviceItems as $serviceItem)
                            <tr>
                                <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $serviceItem->service?->name ?? '—' }}</td>
                                @if ($showRoomColumn)
                                    <td>{{ $serviceItem->bookingItemRoom?->room?->room_number ?? 'Cả đơn' }}</td>
                                @endif
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
                            @if ($showRoomColumn)
                                <th>Phòng</th>
                            @endif
                            <th class="text-right">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incidentalItems as $item)
                            <tr>
                                <td>{{ $item->type === 'service' ? 'Dịch vụ' : 'Phụ phí' }}</td>
                                <td>{{ $item->description }}</td>
                                @if ($showRoomColumn)
                                    <td>{{ $item->bookingItemRoom?->room?->room_number ?? 'Cả đơn' }}</td>
                                @endif
                                <td class="text-right font-bold">{{ number_format($item->amount, 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($room)
            @php
                $amounts = $settlement ?? (object) $roomPreview;
                $roomSubtotalAfterDiscount = max(0, $roomBaseCharge + $roomChildSurcharge + $roomExtraBedSurcharge - $discountShare);
            @endphp
            <div class="mt-6 flex justify-end">
                <div class="w-full space-y-2 text-sm sm:max-w-sm">
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span>Giá phòng (riêng phòng {{ $room->room->room_number ?? '' }})</span>
                        <span>{{ number_format($roomBaseCharge, 0, ',', '.') }}đ</span>
                    </div>
                    @if ($roomChildSurcharge > 0)
                        <div class="text-slate-500 dark:text-slate-400">
                            <div class="flex items-center justify-between">
                                <span>Phụ thu trẻ em</span>
                                <span>{{ number_format($roomChildSurcharge, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="text-xs">(đã chia đều {{ number_format($room->bookingItem->child_surcharge, 0, ',', '.') }}đ phụ thu trẻ em của cả {{ $room->bookingItem->quantity }} phòng cho phòng này — không phải đơn giá riêng 1 phòng)</div>
                        </div>
                    @endif
                    @if ($roomExtraBedSurcharge > 0)
                        <div class="text-slate-500 dark:text-slate-400">
                            <div class="flex items-center justify-between">
                                <span>Phụ thu giường phụ</span>
                                <span>{{ number_format($roomExtraBedSurcharge, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="text-xs">(đã chia đều {{ number_format($room->bookingItem->extra_bed_surcharge, 0, ',', '.') }}đ phụ thu giường phụ của cả {{ $room->bookingItem->quantity }} phòng cho phòng này — không phải đơn giá 1 giường phụ)</div>
                        </div>
                    @endif
                    @if ($discountShare > 0)
                        <div class="text-slate-500 dark:text-slate-400">
                            <div class="flex items-center justify-between">
                                <span>Giảm giá đoàn/nhóm (phần của phòng này){{ $booking->promotion ? ' — ' . $booking->promotion->code : '' }}</span>
                                <span class="text-accent-dark">-{{ number_format($discountShare, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="text-xs">(đã chia đều tổng giảm giá {{ number_format($booking->discount_amount, 0, ',', '.') }}đ của cả đơn cho {{ $booking->totalRoomsRequired() }} phòng)</div>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-slate-200 pt-2 font-bold text-slate-800 dark:border-slate-800 dark:text-slate-100">
                        <span>Tổng tiền phòng (sau giảm giá)</span>
                        <span>{{ number_format($roomSubtotalAfterDiscount, 0, ',', '.') }}đ</span>
                    </div>
                    @if ($amounts->service_charge > 0)
                        <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                            <span>Dịch vụ/phụ phí riêng phòng</span>
                            <span>{{ number_format($amounts->service_charge, 0, ',', '.') }}đ</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span>Đã thanh toán trước (đặt cọc/trả trước đã phân bổ)</span>
                        <span class="text-accent-dark">-{{ number_format($amounts->deposit_credit, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="invoice-total-box mt-1 flex items-center justify-between rounded-xl bg-primary-light px-4 py-3.5 dark:bg-primary/15">
                        <span class="font-heading text-base font-extrabold text-slate-900 dark:text-white">{{ $settlement ? 'ĐÃ THU KHI TRẢ PHÒNG' : 'DỰ KIẾN PHẢI THU KHI TRẢ PHÒNG' }}</span>
                        <span class="font-heading text-xl font-extrabold text-primary">{{ number_format($settlement ? $settlement->amount_collected : max(0, $amounts->amount_due), 0, ',', '.') }}đ</span>
                    </div>
                </div>
            </div>

            <span class="section-kicker mt-6 block">Trạng thái phòng</span>
            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                <div class="grid gap-y-1.5 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Nhận phòng</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100">{{ $room->checked_in_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>
                    @if ($settlement)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Trả phòng</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ $room->checked_out_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Hình thức thu</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ $settlement->method ?? '—' }}</span>
                        </div>
                    @else
                        <div class="text-xs text-slate-500 dark:text-slate-400">Phòng chưa trả — số tiền trên chỉ là DỰ KIẾN, sẽ chốt chính xác lúc trả phòng.</div>
                    @endif
                </div>
            </div>
        @else
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
                    @php
                        $roomPaid = $booking->paidAmount();
                        $incidentalPaid = $incidentalItems->isNotEmpty() && $incidentalInvoice->isPaid()
                            ? (float) $incidentalInvoice->total_amount
                            : 0.0;
                        $totalPaid = $roomPaid + $incidentalPaid;
                        $totalDue = round($grandTotal - $totalPaid);
                    @endphp
                    <div class="grid gap-y-1.5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Trạng thái</span>
                            <span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span>
                        </div>
                        @if ($booking->payment->surcharge_amount > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Phụ phí phát sinh</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($booking->payment->surcharge_amount, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Lý do phụ phí</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $booking->payment->surcharge_note }}</span>
                            </div>
                        @endif
                        @foreach ($booking->paymentBreakdown() as $row)
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Đã thanh toán tiền phòng</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $row['label'] }} — {{ number_format($row['amount'], 0, ',', '.') }}đ</span>
                            </div>
                        @endforeach
                        @if ($incidentalPaid > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Đã thanh toán tiền dịch vụ phát sinh</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">Thanh toán tại quầy lễ tân — {{ number_format($incidentalPaid, 0, ',', '.') }}đ</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between border-t border-slate-200 pt-1.5 dark:border-slate-800">
                            <span class="text-slate-500 dark:text-slate-400">TỔNG ĐÃ THANH TOÁN</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ number_format($totalPaid, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">{{ $totalDue < 0 ? 'SỐ DƯ HOÀN LẠI' : 'CÒN PHẢI THANH TOÁN' }}</span>
                            <span class="font-bold {{ $totalDue < 0 ? 'text-accent-dark' : 'text-slate-800 dark:text-slate-100' }}">{{ number_format(abs($totalDue), 0, ',', '.') }}đ</span>
                        </div>
                        @if ($booking->payment->paid_at)
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Thời gian thanh toán</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $booking->payment->paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
                            </div>
                        @elseif ($booking->payment->deposit_paid_at)
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Thời gian đặt cọc</span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $booking->payment->deposit_paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-sm text-slate-500 dark:text-slate-400">Chưa có thông tin thanh toán</div>
                @endif
            </div>
        @endif

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
