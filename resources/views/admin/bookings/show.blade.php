@extends('layouts.admin')

@section('title', 'Đơn ' . $booking->booking_code . ' · Homi Admin')
@section('page_title', 'Đơn ' . $booking->booking_code)
@section('page_subtitle', 'Nhận phòng ' . $booking->check_in->format('d/m/Y') . ' · Trả phòng ' . $booking->check_out->format('d/m/Y'))

@section('content')
<div class="space-y-5">

    {{-- Trạng thái & hành động --}}
    <div class="card">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge {{ $booking->status->badgeClass() }} !px-3 !py-1.5 !text-sm">{{ $booking->status->label() }}</span>
                @if ($booking->payment)
                    <span class="badge {{ $booking->payment->status->badgeClass() }} !px-3 !py-1.5 !text-sm">{{ $booking->payment->status->label() }}</span>
                @endif
            </div>

            <div class="action-row m-0">
                @if ($booking->payment)
                    <a href="{{ route('admin.bookings.invoice', $booking->id) }}" target="_blank" class="btn btn-outline btn-sm">🖨 Xem hóa đơn</a>
                @endif

                @if ($booking->canConfirm())
                    <form method="POST" action="{{ route('admin.bookings.confirm', $booking->id) }}"
                        onsubmit="return confirm('Xác nhận đơn {{ $booking->booking_code }}?');">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">Xác nhận đơn</button>
                    </form>
                @endif

                @if ($booking->canCheckIn())
                    <a href="{{ route('admin.bookings.check-in.show', $booking->id) }}" class="btn btn-primary btn-sm">Check-in</a>
                @endif

                @if ($booking->status === \App\Enums\BookingStatus::CHECKED_IN && $booking->hasRoomsPendingCheckout())
                    @if ($booking->isEarlyCheckoutToday())
                        <a href="{{ route('admin.bookings.check-out.show', $booking->id) }}" class="btn btn-primary btn-sm">⚠ Trả phòng sớm</a>
                    @else
                        <a href="{{ route('admin.bookings.check-out.show', $booking->id) }}" class="btn btn-primary btn-sm">Check-out</a>
                    @endif
                @endif

                @if ($booking->canComplete())
                    <form method="POST" action="{{ route('admin.bookings.complete', $booking->id) }}"
                        onsubmit="return confirm('Đánh dấu hoàn thành đơn {{ $booking->booking_code }}?');">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">Đánh dấu hoàn thành</button>
                    </form>
                @endif

                @if ($booking->canCancelByAdmin())
                    @php
                        // Khớp đúng logic BookingService::attemptRefund() (admin hủy
                        // luôn feePercent=0): số hoàn = amount_collected, KHÔNG dùng
                        // paidAmount() vì hàm đó fallback sang deposit_amount cho đơn
                        // mới đặt cọc — tiền cọc KHÔNG được hoàn theo chính sách "cọc
                        // giữ chỗ" (payDepositDemo()/admin đánh dấu DEPOSIT_PAID không
                        // ghi amount_collected).
                        $refundAmount = (float) ($booking->payment->amount_collected ?? 0);
                        $isDepositOnly = $refundAmount <= 0
                            && $booking->payment
                            && $booking->payment->status === \App\Enums\PaymentStatus::DEPOSIT_PAID;

                        $cancelConfirmMessage = "Hủy đơn {$booking->booking_code}?";
                        if ($refundAmount > 0) {
                            $cancelConfirmMessage .= ' Sẽ hoàn ' . number_format($refundAmount, 0, ',', '.') . 'đ cho khách.';
                        } elseif ($isDepositOnly) {
                            $cancelConfirmMessage .= ' Tiền cọc đã đặt sẽ KHÔNG được hoàn (theo chính sách cọc giữ chỗ).';
                        }
                    @endphp
                    <form method="POST" action="{{ route('admin.bookings.cancel', $booking->id) }}"
                        onsubmit="return confirm('{{ $cancelConfirmMessage }}');">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">Hủy đơn</button>
                    </form>
                @endif

                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline btn-sm">← Danh sách</a>
            </div>
        </div>

        @if ($booking->status === \App\Enums\BookingStatus::CONFIRMED && ! $booking->canCheckIn())
            <div class="alert alert-warning mt-4 mb-0">Khách cần đặt cọc hoặc thanh toán trước khi có thể check-in.</div>
        @endif

        @if ($booking->canCancelByAdmin() && $refundAmount > 0)
            <div class="alert alert-warning mt-4 mb-0">Nếu hủy đơn này, sẽ hoàn {{ number_format($refundAmount, 0, ',', '.') }}đ cho khách.</div>
        @elseif ($booking->canCancelByAdmin() && $isDepositOnly)
            <div class="alert alert-warning mt-4 mb-0">Nếu hủy đơn này, tiền cọc đã đặt sẽ KHÔNG được hoàn (theo chính sách cọc giữ chỗ).</div>
        @endif

    </div>

    {{-- Phòng đã đặt --}}
    <div class="card">
        <div class="section-kicker">Phòng đã đặt</div>
        <div class="table-wrapper mt-3">
            <table>
                <thead>
                    <tr>
                        <th>Loại phòng</th>
                        <th>Phòng · Nhận/trả phòng</th>
                        <th class="text-center">SL</th>
                        <th>Số khách</th>
                        <th class="text-right">Giá/đêm</th>
                        <th class="text-center">Số đêm</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($booking->bookingItems as $item)
                        <tr>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $item->roomType->name ?? '—' }}</td>
                            <td>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Đặt {{ $booking->check_in->format('d/m/Y') }} → {{ $booking->check_out->format('d/m/Y') }}</div>
                                @forelse ($item->bookingItemRooms as $bir)
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        <span class="font-bold text-slate-800 dark:text-slate-100">{{ $bir->room->room_number ?? '—' }}</span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">
                                            Nhận {{ $bir->checked_in_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') ?? '—' }}
                                            @if ($bir->checked_out_at)
                                                · Trả {{ $bir->checked_out_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                                            @endif
                                        </span>
                                        @if ($bir->checked_in_at && ! $bir->checked_out_at)
                                            <span class="badge badge-blue">Chưa trả phòng</span>
                                        @endif
                                        <a href="{{ route('admin.bookings.invoice', $booking->id) }}?room={{ $bir->id }}" target="_blank" class="text-xs font-semibold text-primary hover:underline">🖨 Hóa đơn phòng</a>
                                    </div>
                                @empty
                                    <span class="text-slate-400">—</span>
                                @endforelse
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td>{{ $item->adults }} người lớn{{ $item->children ? ', ' . $item->children . ' trẻ em' : '' }}{{ $item->infants ? ', ' . $item->infants . ' sơ sinh' : '' }}</td>
                            <td class="text-right">{{ number_format($item->price_per_night, 0, ',', '.') }}đ</td>
                            <td class="text-center">{{ $item->nights }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Dịch vụ phát sinh --}}
    @php
        $extraBedItems = $booking->bookingItems->where('extra_bed_surcharge', '>', 0);
    @endphp
    @if ($booking->serviceItems->isNotEmpty() || $extraBedItems->isNotEmpty())
        <div class="card">
            <div class="section-kicker">Dịch vụ phát sinh</div>
            <div class="table-wrapper mt-3">
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
                        @foreach ($extraBedItems as $item)
                            <tr>
                                <td class="font-semibold text-slate-800 dark:text-slate-100">
                                    Phụ thu giường phụ ({{ $item->roomType->name ?? '—' }})
                                    @if ($booking->payment)
                                        <div class="mt-1"><span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span></div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ $item->extra_beds }}
                                    <div class="text-xs font-normal text-slate-500 dark:text-slate-400">× {{ $item->nights }} đêm</div>
                                </td>
                                <td class="text-right">{{ number_format($item->extra_beds > 0 && $item->nights > 0 ? $item->extra_bed_surcharge / $item->extra_beds / $item->nights : $item->extra_bed_surcharge, 0, ',', '.') }}đ/đêm</td>
                                <td class="text-right font-bold">{{ number_format($item->extra_bed_surcharge, 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach
                        @php
                            $incidentalPaidNow = $booking->incidentalInvoice?->isPaid();
                        @endphp
                        @foreach ($booking->serviceItems as $serviceItem)
                            <tr>
                                <td class="font-semibold text-slate-800 dark:text-slate-100">
                                    {{ $serviceItem->service?->name ?? '—' }}
                                    <div class="mt-1"><span class="badge {{ $incidentalPaidNow ? 'badge-green' : 'badge-orange' }}">{{ $incidentalPaidNow ? 'Đã thanh toán' : 'Thanh toán khi trả phòng' }}</span></div>
                                </td>
                                <td class="text-center">{{ $serviceItem->quantity }}</td>
                                <td class="text-right">{{ number_format($serviceItem->unit_price, 0, ',', '.') }}đ</td>
                                <td class="text-right font-bold">{{ number_format($serviceItem->subtotal, 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right font-bold">Tổng phụ phí dịch vụ phát sinh</td>
                            <td class="text-right font-bold text-primary">{{ number_format($extraBedItems->sum('extra_bed_surcharge') + $booking->serviceItems->sum('subtotal'), 0, ',', '.') }}đ</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        {{-- Thông tin khách hàng --}}
        <div class="card">
            <div class="section-kicker">Thông tin khách hàng</div>
            <div class="info-list mt-3">
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
                @if ($booking->national_id)
                    <div class="info-item">
                        <span class="label">Số CCCD/CMND</span>
                        <span class="value">{{ $booking->national_id }}</span>
                    </div>
                @endif
                <div class="info-item">
                    <span class="label">Tổng số khách</span>
                    <span class="value">{{ $booking->adults }} người lớn{{ $booking->children ? ', ' . $booking->children . ' trẻ em' : '' }}{{ $booking->infants ? ', ' . $booking->infants . ' sơ sinh' : '' }}</span>
                </div>
                @if ($booking->note)
                    <div class="info-item">
                        <span class="label">Ghi chú</span>
                        <span class="value">{{ $booking->note }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Thanh toán --}}
        <div class="card">
            <div class="section-kicker">Thanh toán</div>

            @if ($booking->payment)
                @php
                    $extraBedTotal = $booking->bookingItems->sum('extra_bed_surcharge');
                    $isCashMethod = in_array($booking->payment->method, [\App\Enums\PaymentMethod::PAY_AT_HOTEL, \App\Enums\PaymentMethod::CASH_WITH_DEPOSIT]);
                    $dueAtCheckInLabel = $isCashMethod ? 'Thanh toán tiền mặt khi nhận phòng' : 'Thanh toán chuyển khoản khi nhận phòng';
                    $pendingIncidentalTotal = (float) ($booking->incidentalInvoice?->isOpen() ? $booking->incidentalInvoice->total_amount : 0);
                @endphp
                <div class="info-list mt-3">
                    @if ($booking->payment->deposit_paid_at)
                        <div class="info-item">
                            <span class="label">Đã đặt cọc</span>
                            <span class="value">
                                {{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ lúc {{ $booking->payment->deposit_paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                                <div class="text-xs font-normal text-slate-500 dark:text-slate-400">
                                    {{ $booking->payment->method->label() }}
                                    @if ($extraBedTotal > 0)
                                        · đã tính cả tiền giường phụ
                                    @endif
                                </div>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="label">Số tiền cần thanh toán khi nhận phòng</span>
                            <span class="value">
                                {{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ
                                <span class="badge badge-orange">{{ $dueAtCheckInLabel }}</span>
                                @if ($booking->payment->paid_at)
                                    <div class="mt-1">
                                        <span class="badge badge-blue-solid">Đã thanh toán lúc {{ $booking->payment->paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endif
                            </span>
                        </div>
                    @else
                        <div class="info-item">
                            <span class="label">Số tiền</span>
                            <span class="value">
                                {{ number_format($booking->payment->amount, 0, ',', '.') }}đ
                                <div class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ $booking->payment->method->label() }}</div>
                                @if ($booking->payment->paid_at)
                                    <div class="mt-1">
                                        <span class="badge badge-blue-solid">Đã thanh toán lúc {{ $booking->payment->paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endif
                            </span>
                        </div>
                    @endif
                    @if ($pendingIncidentalTotal > 0)
                        <div class="info-item">
                            <span class="label">Phụ phí cần thanh toán khi trả phòng</span>
                            <span class="value">
                                {{ number_format($pendingIncidentalTotal, 0, ',', '.') }}đ
                                <span class="badge badge-orange">Thanh toán khi trả phòng</span>
                            </span>
                        </div>
                    @endif
                    @if ($booking->payment->surcharge_amount > 0)
                        {{-- Dữ liệu lịch sử trước khi tách hóa đơn phát sinh riêng — giữ hiển thị để không mất thông tin đơn cũ. --}}
                        <div class="info-item">
                            <span class="label">Phụ phí phát sinh (cũ)</span>
                            <span class="value">{{ number_format($booking->payment->surcharge_amount, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Lý do phụ phí (cũ)</span>
                            <span class="value">{{ $booking->payment->surcharge_note }}</span>
                        </div>
                    @endif
                </div>
            @else
                <div class="empty-box mt-3">Đơn này chưa có thông tin thanh toán.</div>
            @endif
        </div>
    </div>

    @if ($booking->payment)
        @php
            $incidentalInvoice = $booking->incidentalInvoice;
            $incidentalItems = $incidentalInvoice?->items ?? collect();
        @endphp
        @if ($incidentalItems->isNotEmpty())
            <div class="card">
                <div class="section-kicker">Hóa đơn phát sinh</div>
                <div class="info-list mt-3">
                    @foreach ($incidentalItems as $item)
                        <div class="info-item">
                            <span class="label">{{ $item->description }}</span>
                            <span class="value">{{ number_format($item->amount, 0, ',', '.') }}đ</span>
                        </div>
                    @endforeach
                    <div class="info-item">
                        <span class="label">Tổng hóa đơn phát sinh</span>
                        <span class="value">
                            {{ number_format($incidentalInvoice->total_amount, 0, ',', '.') }}đ
                            <span class="badge {{ $incidentalInvoice->isPaid() ? 'badge-green' : 'badge-orange' }}">{{ $incidentalInvoice->isPaid() ? 'Đã thanh toán' : 'Thanh toán khi trả phòng' }}</span>
                        </span>
                    </div>
                    @if ($incidentalInvoice->isPaid() && $incidentalInvoice->paid_at)
                        <div class="info-item">
                            <span class="label">Đã thanh toán lúc</span>
                            <span class="value">{{ $incidentalInvoice->paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @php
            $roomOnlyTotal = $booking->bookingItems->sum(fn ($item) => $item->subtotal + $item->child_surcharge);
            $incidentalTotal = (float) ($incidentalInvoice->total_amount ?? 0);
            $grandTotal = (float) $booking->total_amount + $incidentalTotal;
        @endphp
        <div class="card">
            <div class="section-kicker">Tổng số tiền phải trả</div>
            <div class="info-list mt-3">
                <div class="info-item">
                    <span class="label">Tiền phòng</span>
                    <span class="value">{{ number_format($roomOnlyTotal, 0, ',', '.') }}đ</span>
                </div>
                @if ($extraBedTotal > 0)
                    <div class="info-item">
                        <span class="label">Tiền giường phụ</span>
                        <span class="value">{{ number_format($extraBedTotal, 0, ',', '.') }}đ</span>
                    </div>
                @endif
                @if ($booking->discount_amount > 0)
                    <div class="info-item">
                        <span class="label">Giảm giá</span>
                        <span class="value text-accent">-{{ number_format($booking->discount_amount, 0, ',', '.') }}đ</span>
                    </div>
                @endif
                @if ($incidentalTotal > 0)
                    <div class="info-item">
                        <span class="label">Tiền phát sinh</span>
                        <span class="value">{{ number_format($incidentalTotal, 0, ',', '.') }}đ</span>
                    </div>
                @endif
                <div class="info-item">
                    <span class="label font-bold text-slate-700 dark:text-slate-200">Tổng cộng</span>
                    <span class="value text-lg text-primary">{{ number_format($grandTotal, 0, ',', '.') }}đ</span>
                </div>
                @php
                    $incidentalPaid = $incidentalItems->isNotEmpty() && $incidentalInvoice->isPaid()
                        ? (float) $incidentalInvoice->total_amount
                        : 0.0;
                    $roomPaidAmount = $booking->paidAmount();
                    $totalPaid = $roomPaidAmount + $incidentalPaid;
                    $totalDue  = round($grandTotal - $totalPaid);
                @endphp
                @if ($booking->payment->deposit_paid_at)
                    <div class="info-item">
                        <span class="label"><span class="badge badge-green">Đã đặt cọc</span></span>
                        <span class="value">{{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ</span>
                    </div>
                    @if ($booking->payment->status === \App\Enums\PaymentStatus::PAID)
                        <div class="info-item">
                            <span class="label"><span class="badge badge-green">Đã thanh toán khi nhận phòng</span></span>
                            <span class="value">{{ number_format($roomPaidAmount - $booking->payment->deposit_amount, 0, ',', '.') }}đ</span>
                        </div>
                    @endif
                @elseif ($roomPaidAmount > 0)
                    <div class="info-item">
                        <span class="label"><span class="badge badge-green">Đã thanh toán tiền phòng{{ $extraBedTotal > 0 ? ' (gồm giường phụ)' : '' }}</span></span>
                        <span class="value">{{ number_format($roomPaidAmount, 0, ',', '.') }}đ</span>
                    </div>
                @endif
                @if ($incidentalPaid > 0)
                    <div class="info-item">
                        <span class="label"><span class="badge badge-green">Đã thanh toán tiền phát sinh</span></span>
                        <span class="value">{{ number_format($incidentalPaid, 0, ',', '.') }}đ</span>
                    </div>
                @endif
                @if ($totalPaid > 0)
                    <div class="info-item">
                        <span class="label font-bold"><span class="badge badge-green">Tổng cộng đã thanh toán</span></span>
                        <span class="value font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($totalPaid, 0, ',', '.') }}đ</span>
                    </div>
                @endif
                <div class="info-item">
                    <span class="label">{{ $totalDue < 0 ? 'Số dư hoàn lại' : 'Còn phải thanh toán' }}</span>
                    <span class="value {{ $totalDue < 0 ? 'text-accent' : 'text-red-600 dark:text-red-400' }}">{{ number_format(abs($totalDue), 0, ',', '.') }}đ</span>
                </div>
            </div>

            @if ($booking->canMarkPaymentAsPaid() || ($booking->status === \App\Enums\BookingStatus::CANCELLED && $booking->payment->status->canTransitionTo(\App\Enums\PaymentStatus::REFUNDED)))
                <div class="action-row mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                    @if ($booking->canMarkPaymentAsPaid())
                        <form method="POST" action="{{ route('admin.bookings.update-payment', $booking->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="paid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                {{ $booking->payment->status === \App\Enums\PaymentStatus::DEPOSIT_PAID ? 'Xác nhận đã thu đủ tiền mặt còn lại' : 'Đánh dấu đã thanh toán' }}
                            </button>
                        </form>
                    @endif

                    @if ($booking->status === \App\Enums\BookingStatus::CANCELLED && $booking->payment->status->canTransitionTo(\App\Enums\PaymentStatus::REFUNDED))
                        <form method="POST" action="{{ route('admin.bookings.update-payment', $booking->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="refunded">
                            <button type="submit" class="btn btn-outline btn-sm">Đánh dấu đã hoàn tiền</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        @php
            $latestEarlyCheckin  = $booking->earlyCheckinRequests->sortByDesc('created_at')->first();
            $latestLateCheckout  = $booking->lateCheckoutRequests->sortByDesc('created_at')->first();
            $latestExtraBed      = $booking->extraBedRequests->sortByDesc('created_at')->first();
            $latestGroupDiscount = $booking->groupDiscountRequests->sortByDesc('created_at')->first();
        @endphp
        @if ($latestEarlyCheckin || $latestLateCheckout || $latestExtraBed || $latestGroupDiscount)
            <div class="card">
                <div class="section-kicker">Yêu cầu liên quan</div>
                <div class="info-list mt-3">
                    @if ($latestEarlyCheckin)
                        @php
                            $eciBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$latestEarlyCheckin->status] ?? 'badge-green';
                            $eciLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$latestEarlyCheckin->status] ?? $latestEarlyCheckin->status;
                        @endphp
                        <div class="info-item">
                            <span class="label">Nhận phòng sớm</span>
                            <span class="value">
                                Lúc {{ substr($latestEarlyCheckin->requested_arrival_time, 0, 5) }}
                                ({{ $latestEarlyCheckin->hours_early }} giờ, {{ number_format($latestEarlyCheckin->fee_amount, 0, ',', '.') }}đ)
                                <span class="badge {{ $eciBadge }}">{{ $eciLabel }}</span>
                                — <a href="{{ route('admin.early-checkin-requests.show', $latestEarlyCheckin->id) }}" class="font-semibold text-primary hover:underline">Xem</a>
                            </span>
                        </div>
                    @endif

                    @if ($latestLateCheckout)
                        @php
                            $lcoBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$latestLateCheckout->status] ?? 'badge-green';
                            $lcoLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$latestLateCheckout->status] ?? $latestLateCheckout->status;
                        @endphp
                        <div class="info-item">
                            <span class="label">Trả phòng muộn</span>
                            <span class="value">
                                Tới {{ substr($latestLateCheckout->requested_checkout_time, 0, 5) }}
                                ({{ $latestLateCheckout->hours_late }} giờ, {{ number_format($latestLateCheckout->fee_amount, 0, ',', '.') }}đ)
                                <span class="badge {{ $lcoBadge }}">{{ $lcoLabel }}</span>
                                — <a href="{{ route('admin.late-checkout-requests.show', $latestLateCheckout->id) }}" class="font-semibold text-primary hover:underline">Xem</a>
                            </span>
                        </div>
                    @endif

                    @if ($latestExtraBed)
                        @php
                            $ebBadge = ['pending' => 'badge-orange', 'waitlisted' => 'badge-blue', 'resolved' => 'badge-green'][$latestExtraBed->status] ?? 'badge-green';
                            $ebLabel = ['pending' => 'Chờ xử lý', 'waitlisted' => 'Waitlist', 'resolved' => 'Đã xử lý'][$latestExtraBed->status] ?? $latestExtraBed->status;
                        @endphp
                        <div class="info-item">
                            <span class="label">Giường phụ</span>
                            <span class="value">
                                Cần {{ $latestExtraBed->requested_extra_beds }}, còn {{ $latestExtraBed->available_extra_beds }}
                                <span class="badge {{ $ebBadge }}">{{ $ebLabel }}</span>
                                — <a href="{{ route('admin.extra-bed-requests.show', $latestExtraBed->id) }}" class="font-semibold text-primary hover:underline">Xem</a>
                            </span>
                        </div>
                    @endif

                    @if ($latestGroupDiscount)
                        @php
                            $gdBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$latestGroupDiscount->status] ?? 'badge-green';
                            $gdLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$latestGroupDiscount->status] ?? $latestGroupDiscount->status;
                        @endphp
                        <div class="info-item">
                            <span class="label">Ưu đãi đoàn</span>
                            <span class="value">
                                {{ (float) $latestGroupDiscount->requested_percent }}%
                                <span class="badge {{ $gdBadge }}">{{ $gdLabel }}</span>
                                — <a href="{{ route('admin.group-discount-requests.show', $latestGroupDiscount->id) }}" class="font-semibold text-primary hover:underline">Xem</a>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($booking->status === \App\Enums\BookingStatus::CHECKED_IN)
            <div class="card">
                <div class="section-kicker">Thao tác trong lúc lưu trú</div>
                <div class="mt-3 space-y-3">
                    <form method="POST" action="{{ route('admin.bookings.services.store', $booking->id) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @include('partials.surcharge-item-select', ['items' => $activeServices, 'hiddenField' => 'service_id', 'placeholder' => 'Gõ để tìm dịch vụ...'])
                        <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="20" value="1" title="Số lượng">
                        <input type="number" name="amount" class="input surcharge-amount" style="width:150px;" min="1000" step="1000" placeholder="Số tiền (nếu chưa có giá cố định)">
                        <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Ghi chú (không bắt buộc)">
                        @include('partials._room-select')
                        <button type="submit" class="btn btn-outline btn-sm">🔵 Thêm dịch vụ phát sinh</button>
                    </form>

                    <form method="POST" action="{{ route('admin.bookings.surcharge.store', $booking->id) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @include('partials.surcharge-item-select', ['items' => $damageItems, 'hiddenField' => 'surcharge_item_id', 'placeholder' => 'Gõ để tìm đồ hỏng/mất...', 'notePrefix' => 'Bồi thường: '])
                        <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="99" value="1" title="Số lượng">
                        <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                        <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do (VD: hư hỏng đồ...)" required>
                        @include('partials._room-select')
                        <button type="submit" class="btn btn-outline btn-sm">🔴 Thêm phụ phí hỏng/mất đồ</button>
                    </form>

                    <form method="POST" action="{{ route('admin.bookings.surcharge.store', $booking->id) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @include('partials.surcharge-item-select', ['items' => $violationItems, 'hiddenField' => 'surcharge_item_id', 'placeholder' => 'Gõ để tìm vi phạm...', 'notePrefix' => 'Vi phạm: '])
                        <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="99" value="1" title="Số lượng">
                        <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                        <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do" required>
                        @include('partials._room-select')
                        <button type="submit" class="btn btn-outline btn-sm">🟠 Thêm phụ phí vi phạm</button>
                    </form>

                    <form method="POST" action="{{ route('admin.bookings.surcharge.store', $booking->id) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @include('partials.surcharge-item-select', ['items' => $cleaningItems, 'hiddenField' => 'surcharge_item_id', 'placeholder' => 'Gõ để tìm khoản vệ sinh...', 'notePrefix' => 'Vệ sinh đặc biệt: '])
                        <input type="number" name="quantity" class="input surcharge-quantity" style="width:70px;" min="1" max="99" value="1" title="Số lượng">
                        <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                        <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do" required>
                        @include('partials._room-select')
                        <button type="submit" class="btn btn-outline btn-sm">🟡 Thêm phụ phí vệ sinh đặc biệt</button>
                    </form>

                    <form method="POST" action="{{ route('admin.bookings.extend-stay.store', $booking->id) }}" id="extend-stay-form" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <span class="text-xs text-slate-500 dark:text-slate-400">Từ {{ $booking->check_out->format('d/m/Y') }} đến:</span>
                        <input type="date" name="new_check_out" id="extend-checkout" class="input" style="width:auto;" min="{{ $booking->check_out->copy()->addDay()->toDateString() }}" required>
                        <label id="extend-switch-toggle-label" class="text-xs text-slate-500 dark:text-slate-400" style="display:none; white-space:nowrap;">
                            <input type="checkbox" id="extend-switch-toggle"> Muốn đổi sang phòng khác
                        </label>
                        <select id="extend-switch-type" class="input" style="width:auto; display:none;">
                            <option value="">-- Chọn loại phòng thay thế --</option>
                        </select>
                        <select id="extend-switch-room" class="input" style="width:auto; display:none;">
                            <option value="">-- Chọn phòng --</option>
                        </select>
                        <input type="hidden" name="switch_room_type_id" id="extend-switch-type-input">
                        <input type="hidden" name="switch_room_id" id="extend-switch-room-input">
                        <span id="extend-preview" class="text-xs text-slate-500 dark:text-slate-400"></span>
                        <button type="submit" id="extend-submit-btn" class="btn btn-outline btn-sm">📅 Gia hạn thời gian thuê phòng</button>
                    </form>
                </div>
            </div>
        @endif
    @endif

    {{-- Nhật ký thao tác --}}
    <div class="card">
        @include('bookings._activity-log', [
            'booking'   => $booking,
            'timeline'  => $timeline,
            'chatRoute' => $booking->user_id ? route('admin.chat.show', $booking->user_id) : null,
        ])
    </div>
</div>

@if ($booking->status === \App\Enums\BookingStatus::CHECKED_IN)
    @push('scripts')
        <script>
            (function () {
                const extendInput    = document.getElementById('extend-checkout');
                const extendPreview  = document.getElementById('extend-preview');
                const typeSelect     = document.getElementById('extend-switch-type');
                const roomSelect     = document.getElementById('extend-switch-room');
                const typeInput      = document.getElementById('extend-switch-type-input');
                const roomInput      = document.getElementById('extend-switch-room-input');
                const submitBtn      = document.getElementById('extend-submit-btn');
                const form           = document.getElementById('extend-stay-form');
                const toggleLabel    = document.getElementById('extend-switch-toggle-label');
                const toggleCheckbox = document.getElementById('extend-switch-toggle');
                const previewUrl     = '{{ route('admin.bookings.extend-stay.preview', $booking->id) }}';

                let alternatives         = [];
                let voluntaryAlternatives = [];
                let normalPreviewText    = '';
                let confirmedRoom        = '';

                function resetSwitchUI() {
                    alternatives = [];
                    voluntaryAlternatives = [];
                    normalPreviewText = '';
                    confirmedRoom = '';
                    typeSelect.style.display = 'none';
                    roomSelect.style.display = 'none';
                    typeSelect.innerHTML = '<option value="">-- Chọn loại phòng thay thế --</option>';
                    roomSelect.innerHTML = '<option value="">-- Chọn phòng --</option>';
                    typeInput.value = '';
                    roomInput.value = '';
                    submitBtn.disabled = false;
                    submitBtn.textContent = '📅 Gia hạn thời gian thuê phòng';
                    toggleLabel.style.display = 'none';
                    toggleCheckbox.checked = false;
                }

                function renderTypeOptions(list) {
                    return '<option value="">-- Chọn loại phòng thay thế --</option>' +
                        list.map(a => {
                            const roomNumbers = a.available_rooms.map(r => r.room_number).join(', ');
                            const bedNote = a.extra_beds_needed ? ', kèm giường phụ' : '';
                            return `<option value="${a.room_type_id}">${a.name} (phòng ${roomNumbers}${bedNote}) — +${Number(a.extra_amount).toLocaleString('vi-VN')}đ</option>`;
                        }).join('');
                }

                async function fetchPreview(switchRoomTypeId, switchRoomId) {
                    let url = `${previewUrl}?new_check_out=${extendInput.value}`;
                    if (switchRoomTypeId) url += `&switch_room_type_id=${switchRoomTypeId}`;
                    if (switchRoomId) url += `&switch_room_id=${switchRoomId}`;

                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

                    return res.json();
                }

                extendInput?.addEventListener('change', async () => {
                    if (! extendInput.value) return;

                    resetSwitchUI();
                    extendPreview.style.color = '';
                    extendPreview.textContent = 'Đang kiểm tra phòng trống...';

                    try {
                        const data = await fetchPreview(null, null);

                        if (! data.ok) {
                            extendPreview.style.color = '#dc2626';
                            extendPreview.textContent = data.message;
                            submitBtn.disabled = true;
                            return;
                        }

                        if (data.needs_switch) {
                            alternatives = data.alternatives;

                            if (alternatives.length === 0) {
                                extendPreview.style.color = '#dc2626';
                                extendPreview.textContent = 'Phòng đang ở đã hết chỗ và không còn loại phòng nào khác trống cho khoảng ngày này.';
                                submitBtn.disabled = true;
                                return;
                            }

                            extendPreview.style.color = '#b45309';
                            extendPreview.textContent = 'Phòng đang ở đã hết chỗ cho khoảng ngày này — vui lòng chọn loại phòng thay thế bên dưới.';

                            typeSelect.innerHTML = renderTypeOptions(alternatives);
                            typeSelect.style.display = '';
                            submitBtn.disabled = true;
                            return;
                        }

                        const roomsText = data.rooms && data.rooms.length ? ` — vẫn ở phòng ${data.rooms.join(', ')}` : '';
                        normalPreviewText = `Thêm ${data.nights_added} đêm${roomsText} — phí thêm: ${Number(data.extra_amount).toLocaleString('vi-VN')}đ`;
                        extendPreview.textContent = normalPreviewText;

                        voluntaryAlternatives = data.switch_alternatives || [];
                        toggleLabel.style.display = voluntaryAlternatives.length ? '' : 'none';
                    } catch (e) {
                        extendPreview.style.color = '#dc2626';
                        extendPreview.textContent = 'Không kiểm tra được, vui lòng thử lại.';
                        submitBtn.disabled = true;
                    }
                });

                toggleCheckbox?.addEventListener('change', () => {
                    typeSelect.innerHTML = '<option value="">-- Chọn loại phòng thay thế --</option>';
                    roomSelect.innerHTML = '<option value="">-- Chọn phòng --</option>';
                    roomSelect.style.display = 'none';
                    typeInput.value = '';
                    roomInput.value = '';
                    confirmedRoom = '';

                    if (toggleCheckbox.checked) {
                        alternatives = voluntaryAlternatives;
                        typeSelect.innerHTML = renderTypeOptions(alternatives);
                        typeSelect.style.display = '';
                        extendPreview.style.color = '#b45309';
                        extendPreview.textContent = 'Vui lòng chọn loại phòng muốn đổi sang bên dưới.';
                        submitBtn.disabled = true;
                    } else {
                        typeSelect.style.display = 'none';
                        extendPreview.style.color = '';
                        extendPreview.textContent = normalPreviewText;
                        submitBtn.textContent = '📅 Gia hạn thời gian thuê phòng';
                        submitBtn.disabled = false;
                    }
                });

                typeSelect?.addEventListener('change', () => {
                    const typeId = typeSelect.value;
                    roomSelect.innerHTML = '<option value="">-- Chọn phòng --</option>';
                    roomInput.value = '';
                    typeInput.value = '';
                    confirmedRoom = '';
                    submitBtn.disabled = true;

                    if (! typeId) {
                        roomSelect.style.display = 'none';
                        return;
                    }

                    const alt = alternatives.find(a => String(a.room_type_id) === typeId);
                    if (! alt) return;

                    roomSelect.innerHTML = '<option value="">-- Chọn phòng --</option>' +
                        alt.available_rooms.map(r => `<option value="${r.id}">${r.room_number}</option>`).join('');
                    roomSelect.style.display = '';
                });

                roomSelect?.addEventListener('change', async () => {
                    const roomId = roomSelect.value;
                    const typeId = typeSelect.value;

                    if (! roomId) {
                        submitBtn.disabled = true;
                        return;
                    }

                    extendPreview.style.color = '';
                    extendPreview.textContent = 'Đang kiểm tra phòng...';

                    try {
                        const data = await fetchPreview(typeId, roomId);

                        if (! data.ok || data.needs_switch) {
                            extendPreview.style.color = '#dc2626';
                            extendPreview.textContent = data.message || 'Phòng vừa chọn không còn hợp lệ, vui lòng chọn lại.';
                            submitBtn.disabled = true;
                            return;
                        }

                        typeInput.value = typeId;
                        roomInput.value = roomId;
                        confirmedRoom = roomSelect.options[roomSelect.selectedIndex]?.textContent ?? '';
                        extendPreview.style.color = '';
                        extendPreview.textContent = `Thêm ${data.nights_added} đêm ở phòng ${confirmedRoom} — phí thêm: ${Number(data.extra_amount).toLocaleString('vi-VN')}đ`;
                        submitBtn.textContent = '📅 Xác nhận đổi phòng & gia hạn';
                        submitBtn.disabled = false;
                    } catch (e) {
                        extendPreview.style.color = '#dc2626';
                        extendPreview.textContent = 'Không kiểm tra được, vui lòng thử lại.';
                        submitBtn.disabled = true;
                    }
                });

                form?.addEventListener('submit', (e) => {
                    const confirmMsg = confirmedRoom
                        ? `Xác nhận đổi sang phòng ${confirmedRoom} và gia hạn tới ngày ${extendInput.value}?`
                        : `Xác nhận gia hạn tới ngày ${extendInput.value}?`;

                    if (! confirm(confirmMsg)) {
                        e.preventDefault();
                    }
                });
            })();
        </script>
    @endpush
@endif
@endsection
