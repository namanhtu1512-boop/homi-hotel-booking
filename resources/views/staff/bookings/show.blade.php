@extends('layouts.staff')

@section('title', 'Đơn ' . $booking->booking_code . ' · Homi Nhân viên')
@section('page_title', 'Đơn ' . $booking->booking_code)
@section('page_subtitle', 'Nhận phòng ' . $booking->check_in->format('d/m/Y') . ' · Trả phòng ' . $booking->check_out->format('d/m/Y'))

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <span class="badge {{ $booking->status->badgeClass() }}">{{ $booking->status->label() }}</span>
            @if ($booking->payment)
                <span class="badge {{ $booking->payment->status->badgeClass() }}">{{ $booking->payment->status->label() }}</span>
            @endif
        </div>

        <div class="action-row">
            @if ($booking->payment)
                <a href="{{ route('staff.bookings.invoice', $booking->id) }}" target="_blank" class="btn btn-outline">🖨 Xem hóa đơn</a>
            @endif

            @if ($booking->canConfirm())
                <form method="POST" action="{{ route('staff.bookings.confirm', $booking->id) }}"
                    onsubmit="return confirm('Xác nhận đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Xác nhận đơn</button>
                </form>
            @endif

            @if ($booking->canCheckIn())
                <a href="{{ route('staff.bookings.check-in.show', $booking->id) }}" class="btn btn-primary">Check-in</a>
            @endif

            @if ($booking->canCheckOut() && $booking->isEarlyCheckoutToday())
                <a href="{{ route('staff.bookings.check-out.show', $booking->id) }}" class="btn btn-primary">⚠ Trả phòng sớm</a>
            @elseif ($booking->canCheckOut())
                <a href="{{ route('staff.bookings.check-out.show', $booking->id) }}" class="btn btn-primary">Check-out</a>
            @endif

            @if ($booking->canComplete())
                <form method="POST" action="{{ route('staff.bookings.complete', $booking->id) }}"
                    onsubmit="return confirm('Đánh dấu hoàn thành đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Đánh dấu hoàn thành</button>
                </form>
            @endif

            @if ($booking->canCancelByAdmin())
                <form method="POST" action="{{ route('staff.bookings.cancel', $booking->id) }}"
                    onsubmit="return confirm('Hủy đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn btn-danger">Hủy đơn</button>
                </form>
            @endif

            <a href="{{ route('staff.bookings.index') }}" class="btn btn-outline">Quay lại danh sách</a>
        </div>
    </div>

    @if ($booking->status === \App\Enums\BookingStatus::CONFIRMED && ! $booking->canCheckIn())
        <div class="alert alert-warning" style="margin-top: 16px;">Khách cần đặt cọc hoặc thanh toán trước khi có thể check-in.</div>
    @endif

    @if ($booking->status === \App\Enums\BookingStatus::CHECKED_IN && ! $booking->canCheckOut())
        <div class="alert alert-warning" style="margin-top: 16px;">Cần thu đủ tiền (kể cả phần dịch vụ/phụ phí phát sinh thêm trong lúc lưu trú, nếu có) trước khi có thể trả phòng.</div>
    @endif

    <div class="section-kicker" style="margin-top: 22px;">Phòng đã đặt</div>
    <div class="table-wrapper" style="margin-top: 10px;">
        <table>
            <thead>
                <tr>
                    <th>Loại phòng</th>
                    <th>Phòng · Nhận/trả phòng</th>
                    <th>Số lượng</th>
                    <th>Số khách</th>
                    <th>Giá/đêm</th>
                    <th>Số đêm</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($booking->bookingItems as $item)
                    <tr>
                        <td>{{ $item->roomType->name ?? '—' }}</td>
                        <td>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Đặt {{ $booking->check_in->format('d/m/Y') }} → {{ $booking->check_out->format('d/m/Y') }}</div>
                            @forelse ($item->bookingItemRooms as $bir)
                                <div>
                                    <strong>{{ $bir->room->room_number ?? '—' }}</strong>
                                    — Nhận {{ $bir->checked_in_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') ?? '—' }}
                                    @if ($bir->checked_out_at)
                                        · Trả {{ $bir->checked_out_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                                    @elseif ($bir->checked_in_at)
                                        · <span class="text-xs text-slate-500 dark:text-slate-400">chưa trả phòng</span>
                                    @endif
                                </div>
                            @empty
                                —
                            @endforelse
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->adults }} người lớn{{ $item->children ? ', ' . $item->children . ' trẻ em' : '' }}{{ $item->infants ? ', ' . $item->infants . ' sơ sinh' : '' }}</td>
                        <td>{{ number_format($item->price_per_night, 0, ',', '.') }}đ</td>
                        <td>{{ $item->nights }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @php
        $extraBedItems = $booking->bookingItems->where('extra_bed_surcharge', '>', 0);
    @endphp
    @if ($booking->serviceItems->isNotEmpty() || $extraBedItems->isNotEmpty())
        <div class="section-kicker" style="margin-top: 22px;">Dịch vụ phát sinh</div>
        <div class="table-wrapper" style="margin-top: 10px;">
            <table>
                <thead>
                    <tr>
                        <th>Dịch vụ</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($extraBedItems as $item)
                        <tr>
                            <td>Phụ thu giường phụ ({{ $item->roomType->name ?? '—' }})</td>
                            <td>{{ $item->extra_beds }}</td>
                            <td>{{ number_format($item->extra_beds > 0 ? $item->extra_bed_surcharge / $item->extra_beds : $item->extra_bed_surcharge, 0, ',', '.') }}đ</td>
                            <td>{{ number_format($item->extra_bed_surcharge, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
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

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 22px;">
        <div>
            <div class="section-kicker">Thông tin khách hàng</div>
            <div class="info-list" style="margin-top: 10px;">
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

        <div>
            <div class="section-kicker">Thanh toán</div>

            @if ($booking->payment)
                @php
                    $extraBedTotal = $booking->bookingItems->sum('extra_bed_surcharge');
                @endphp
                <div class="info-list" style="margin-top: 10px;">
                    <div class="info-item">
                        <span class="label">Phương thức</span>
                        <span class="value">{{ $booking->payment->method->label() }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Số tiền</span>
                        <span class="value">{{ number_format($booking->payment->amount, 0, ',', '.') }}đ</span>
                    </div>
                    @if ($booking->payment->deposit_paid_at)
                        <div class="info-item">
                            <span class="label">Đã đặt cọc</span>
                            <span class="value">
                                {{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ lúc {{ $booking->payment->deposit_paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                                @if ($extraBedTotal > 0)
                                    <div class="text-xs text-slate-500 dark:text-slate-400">(đã tính cả tiền giường phụ)</div>
                                @endif
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="label">Còn lại thu tiền mặt</span>
                            <span class="value">
                                {{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ
                                <span class="badge badge-orange">Thanh toán khi nhận phòng</span>
                            </span>
                        </div>
                    @endif
                    @if ($booking->payment->paid_at)
                        <div class="info-item">
                            <span class="label">Đã thanh toán lúc</span>
                            <span class="value">{{ $booking->payment->paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
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

                @php
                    $incidentalInvoice = $booking->incidentalInvoice;
                    $incidentalItems = $incidentalInvoice?->items ?? collect();
                @endphp
                @if ($incidentalItems->isNotEmpty())
                    <div class="section-kicker mt-4">Hóa đơn phát sinh</div>
                    <div class="info-list mt-2">
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
                    </div>
                @endif

                @php
                    $roomOnlyTotal = $booking->bookingItems->sum(fn ($item) => $item->subtotal + $item->child_surcharge);
                    $incidentalTotal = (float) ($incidentalInvoice->total_amount ?? 0);
                    $grandTotal = (float) $booking->total_amount + $incidentalTotal;
                @endphp
                <div class="section-kicker mt-4">Tổng số tiền phải trả</div>
                <div class="info-list mt-2">
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
                        <span class="label">Tổng cộng</span>
                        <span class="value">{{ number_format($grandTotal, 0, ',', '.') }}đ</span>
                    </div>
                    @php
                        $incidentalPaid = $incidentalItems->isNotEmpty() && $incidentalInvoice->isPaid()
                            ? (float) $incidentalInvoice->total_amount
                            : 0.0;
                        $totalPaid = $booking->paidAmount() + $incidentalPaid;
                        $totalDue  = round($grandTotal - $totalPaid);
                    @endphp
                    <div class="info-item">
                        <span class="label">Đã thanh toán</span>
                        <span class="value">{{ number_format($totalPaid, 0, ',', '.') }}đ</span>
                    </div>
                    <div class="info-item">
                        <span class="label">{{ $totalDue < 0 ? 'Số dư hoàn lại' : 'Còn phải thanh toán' }}</span>
                        <span class="value {{ $totalDue < 0 ? 'text-accent' : '' }}">{{ number_format(abs($totalDue), 0, ',', '.') }}đ</span>
                    </div>
                </div>

                @php
                    $latestEarlyCheckin = $booking->earlyCheckinRequests->sortByDesc('created_at')->first();
                @endphp
                @if ($latestEarlyCheckin)
                    @php
                        $eciBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$latestEarlyCheckin->status] ?? 'badge-green';
                        $eciLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$latestEarlyCheckin->status] ?? $latestEarlyCheckin->status;
                    @endphp
                    <div class="info-list mt-3">
                        <div class="info-item">
                            <span class="label">Yêu cầu nhận phòng sớm</span>
                            <span class="value">
                                Lúc {{ substr($latestEarlyCheckin->requested_arrival_time, 0, 5) }}
                                ({{ $latestEarlyCheckin->hours_early }} giờ, {{ number_format($latestEarlyCheckin->fee_amount, 0, ',', '.') }}đ)
                                <span class="badge {{ $eciBadge }}">{{ $eciLabel }}</span>
                                — <a href="{{ route('staff.early-checkin-requests.show', $latestEarlyCheckin->id) }}">Xem</a>
                            </span>
                        </div>
                    </div>
                @endif

                @php
                    $latestLateCheckout = $booking->lateCheckoutRequests->sortByDesc('created_at')->first();
                @endphp
                @if ($latestLateCheckout)
                    @php
                        $lcoBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$latestLateCheckout->status] ?? 'badge-green';
                        $lcoLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$latestLateCheckout->status] ?? $latestLateCheckout->status;
                    @endphp
                    <div class="info-list mt-3">
                        <div class="info-item">
                            <span class="label">Yêu cầu trả phòng muộn</span>
                            <span class="value">
                                Tới {{ substr($latestLateCheckout->requested_checkout_time, 0, 5) }}
                                ({{ $latestLateCheckout->hours_late }} giờ, {{ number_format($latestLateCheckout->fee_amount, 0, ',', '.') }}đ)
                                <span class="badge {{ $lcoBadge }}">{{ $lcoLabel }}</span>
                                — <a href="{{ route('staff.late-checkout-requests.show', $latestLateCheckout->id) }}">Xem</a>
                            </span>
                        </div>
                    </div>
                @endif

                @php
                    $latestExtraBed = $booking->extraBedRequests->sortByDesc('created_at')->first();
                @endphp
                @if ($latestExtraBed)
                    @php
                        $ebBadge = ['pending' => 'badge-orange', 'waitlisted' => 'badge-blue', 'resolved' => 'badge-green'][$latestExtraBed->status] ?? 'badge-green';
                        $ebLabel = ['pending' => 'Chờ xử lý', 'waitlisted' => 'Waitlist', 'resolved' => 'Đã xử lý'][$latestExtraBed->status] ?? $latestExtraBed->status;
                    @endphp
                    <div class="info-list mt-3">
                        <div class="info-item">
                            <span class="label">Yêu cầu giường phụ</span>
                            <span class="value">
                                Cần {{ $latestExtraBed->requested_extra_beds }}, còn {{ $latestExtraBed->available_extra_beds }}
                                <span class="badge {{ $ebBadge }}">{{ $ebLabel }}</span>
                                — <a href="{{ route('staff.extra-bed-requests.show', $latestExtraBed->id) }}">Xem</a>
                            </span>
                        </div>
                    </div>
                @endif

                @php
                    $latestGroupDiscount = $booking->groupDiscountRequests->sortByDesc('created_at')->first();
                @endphp
                @if ($latestGroupDiscount)
                    @php
                        $gdBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$latestGroupDiscount->status] ?? 'badge-green';
                        $gdLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$latestGroupDiscount->status] ?? $latestGroupDiscount->status;
                    @endphp
                    <div class="info-list mt-3">
                        <div class="info-item">
                            <span class="label">Ưu đãi đoàn</span>
                            <span class="value">
                                {{ (float) $latestGroupDiscount->requested_percent }}%
                                <span class="badge {{ $gdBadge }}">{{ $gdLabel }}</span>
                                — <a href="{{ route('staff.group-discount-requests.show', $latestGroupDiscount->id) }}">Xem</a>
                            </span>
                        </div>
                    </div>
                @endif

                @if ($booking->canApplyGroupDiscount())
                    <div class="mt-3">
                        <div class="section-kicker">Đề xuất giảm giá đoàn thêm</div>
                        <form method="POST" action="{{ route('staff.bookings.group-discount.store', $booking->id) }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap; margin-top: 8px;">
                            @csrf
                            <input type="number" name="percent" min="0.01" max="100" step="0.1" class="input" style="width:100px;" placeholder="VD 5" required>
                            <span>%</span>
                            <input type="text" name="reason" class="input" style="width:260px;" placeholder="Lý do (VD: khách đặt 7 đêm)">
                            <button type="submit" class="btn btn-outline btn-sm">💸 Áp dụng / đề xuất giảm thêm</button>
                        </form>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Nếu mức đề xuất vượt trần cho phép, hệ thống sẽ gửi yêu cầu chờ admin duyệt thay vì áp dụng ngay.</p>
                    </div>
                @endif

                @if ($booking->status === \App\Enums\BookingStatus::CHECKED_IN)
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                        Dịch vụ/phụ phí phát sinh trong lúc khách lưu trú chỉ được ghi nhận ở bước
                        <a href="{{ route('staff.bookings.check-out.show', $booking->id) }}">Trả phòng</a> — không thêm được ở trang này nữa.
                    </p>
                    <div class="action-row" style="margin-top: 16px; flex-wrap: wrap; gap: 12px; flex-direction: column; align-items: stretch;">
                        <form method="POST" action="{{ route('staff.bookings.extend-stay.store', $booking->id) }}" id="extend-stay-form" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                            @csrf
                            <span style="font-size: 12px; color: #64748b;">Từ {{ $booking->check_out->format('d/m/Y') }} đến:</span>
                            <input type="date" name="new_check_out" id="extend-checkout" class="input" style="width:auto;" min="{{ $booking->check_out->copy()->addDay()->toDateString() }}" required>
                            <label id="extend-switch-toggle-label" style="display:none; white-space:nowrap; font-size: 12px; color: #64748b;">
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
                            <span id="extend-preview" style="font-size: 12px; color: #64748b;"></span>
                            <button type="submit" id="extend-submit-btn" class="btn btn-outline btn-sm">📅 Gia hạn thời gian thuê phòng</button>
                        </form>
                    </div>
                @endif

                <div class="action-row" style="margin-top: 16px;">
                    @if ($booking->canMarkPaymentAsPaid())
                        <form method="POST" action="{{ route('staff.bookings.update-payment', $booking->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="paid">
                            <button type="submit" class="btn btn-primary btn-sm">
                                {{ $booking->payment->status === \App\Enums\PaymentStatus::DEPOSIT_PAID ? 'Xác nhận đã thu đủ tiền mặt còn lại' : 'Đánh dấu đã thanh toán' }}
                            </button>
                        </form>
                    @endif

                    @if ($booking->status === \App\Enums\BookingStatus::CANCELLED && $booking->payment->status->canTransitionTo(\App\Enums\PaymentStatus::REFUNDED))
                        <form method="POST" action="{{ route('staff.bookings.update-payment', $booking->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="refunded">
                            <button type="submit" class="btn btn-outline btn-sm">Đánh dấu đã hoàn tiền</button>
                        </form>
                    @endif
                </div>
            @else
                <div class="empty-box">Đơn này chưa có thông tin thanh toán.</div>
            @endif
        </div>
    </div>

    @include('bookings._activity-log', [
        'booking'   => $booking,
        'timeline'  => $timeline,
        'chatRoute' => $booking->user_id ? route('staff.chat.show', $booking->user_id) : null,
    ])
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
                const previewUrl     = '{{ route('staff.bookings.extend-stay.preview', $booking->id) }}';

                let alternatives          = [];
                let voluntaryAlternatives = [];
                let normalPreviewText     = '';
                let confirmedRoom         = '';

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
