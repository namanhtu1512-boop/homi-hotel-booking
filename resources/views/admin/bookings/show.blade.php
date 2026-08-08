@extends('layouts.admin')

@section('title', 'Đơn ' . $booking->booking_code . ' · Homi Admin')
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
                <a href="{{ route('admin.bookings.invoice', $booking->id) }}" target="_blank" class="btn btn-outline">🖨 Xem hóa đơn</a>
            @endif

            @if ($booking->canConfirm())
                <form method="POST" action="{{ route('admin.bookings.confirm', $booking->id) }}"
                    onsubmit="return confirm('Xác nhận đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Xác nhận đơn</button>
                </form>
            @endif

            @if ($booking->canCheckIn())
                <a href="{{ route('admin.bookings.check-in.show', $booking->id) }}" class="btn btn-primary">Check-in</a>
            @endif

            @if ($booking->canCheckOut() && $booking->isEarlyCheckoutToday())
                <a href="{{ route('admin.bookings.check-out.show', $booking->id) }}" class="btn btn-primary">⚠ Trả phòng sớm</a>
            @elseif ($booking->canCheckOut())
                <a href="{{ route('admin.bookings.check-out.show', $booking->id) }}" class="btn btn-primary">Check-out</a>
            @endif

            @if ($booking->canComplete())
                <form method="POST" action="{{ route('admin.bookings.complete', $booking->id) }}"
                    onsubmit="return confirm('Đánh dấu hoàn thành đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Đánh dấu hoàn thành</button>
                </form>
            @endif

            @if ($booking->canCancelByAdmin())
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking->id) }}"
                    onsubmit="return confirm('Hủy đơn {{ $booking->booking_code }}?');">
                    @csrf
                    <button type="submit" class="btn btn-danger">Hủy đơn</button>
                </form>
            @endif

            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline">Quay lại danh sách</a>
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
                    <th>Thành tiền</th>
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
                        <td>
                            {{ number_format($item->subtotal + $item->child_surcharge + $item->extra_bed_surcharge, 0, ',', '.') }}đ
                            @if ($item->child_surcharge > 0)
                                <div class="text-xs text-slate-500 dark:text-slate-400">(gồm {{ number_format($item->child_surcharge, 0, ',', '.') }}đ phụ thu trẻ em)</div>
                            @endif
                            @if ($item->extra_bed_surcharge > 0)
                                <div class="text-xs text-slate-500 dark:text-slate-400">(gồm {{ number_format($item->extra_bed_surcharge, 0, ',', '.') }}đ phụ thu giường phụ)</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($booking->serviceItems->isNotEmpty())
        <div class="section-kicker" style="margin-top: 22px;">Dịch vụ thêm</div>
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
                @if ($booking->discount_amount > 0)
                    <div class="info-item">
                        <span class="label">Tạm tính (trước giảm)</span>
                        <span class="value">{{ number_format($booking->total_amount + $booking->discount_amount, 0, ',', '.') }}đ</span>
                    </div>
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
                    <span class="label">Tổng tiền</span>
                    <span class="value">{{ number_format($booking->total_amount, 0, ',', '.') }}đ</span>
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
                            <span class="value">{{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ lúc {{ $booking->payment->deposit_paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Còn lại thu tiền mặt</span>
                            <span class="value">{{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ</span>
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
                    <div class="table-wrapper mt-2">
                        <table>
                            <thead>
                                <tr>
                                    <th>Loại</th>
                                    <th>Mô tả</th>
                                    <th>Số tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($incidentalItems as $item)
                                    <tr>
                                        <td>{{ $item->type === 'service' ? 'Dịch vụ' : 'Phụ phí' }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td>{{ number_format($item->amount, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">
                                        <strong>Tổng cộng</strong>
                                        <span class="badge {{ $incidentalInvoice->isPaid() ? 'badge-green' : 'badge-orange' }}">{{ $incidentalInvoice->isPaid() ? 'Đã thanh toán' : 'Đang mở' }}</span>
                                    </td>
                                    <td><strong>{{ number_format($incidentalInvoice->total_amount, 0, ',', '.') }}đ</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

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
                                — <a href="{{ route('admin.early-checkin-requests.show', $latestEarlyCheckin->id) }}">Xem</a>
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
                                — <a href="{{ route('admin.late-checkout-requests.show', $latestLateCheckout->id) }}">Xem</a>
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
                                — <a href="{{ route('admin.extra-bed-requests.show', $latestExtraBed->id) }}">Xem</a>
                            </span>
                        </div>
                    </div>
                @endif

                @if ($booking->status === \App\Enums\BookingStatus::CHECKED_IN)
                    <div class="action-row" style="margin-top: 16px; flex-wrap: wrap; gap: 12px;">
                        <form method="POST" action="{{ route('admin.bookings.services.store', $booking->id) }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                            @csrf
                            <select name="service_id" class="input" style="width:auto; max-width:100%;" required>
                                <option value="">-- Chọn dịch vụ --</option>
                                @foreach ($activeServices as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }}{{ $service->availabilityLabel() ? " ({$service->availabilityLabel()})" : '' }} — {{ number_format($service->price, 0, ',', '.') }}đ</option>
                                @endforeach
                            </select>
                            <input type="number" name="quantity" class="input" style="width:70px;" min="1" max="20" value="1" title="Số lượng">
                            <button type="submit" class="btn btn-outline btn-sm">➕ Thêm dịch vụ phát sinh</button>
                        </form>

                        <form method="POST" action="{{ route('admin.bookings.surcharge.store', $booking->id) }}" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                            @csrf
                            @include('partials.surcharge-item-select')
                            <input type="number" name="amount" class="input surcharge-amount" style="width:120px;" min="1000" step="1000" placeholder="Số tiền" required>
                            <input type="text" name="note" class="input surcharge-note" style="width:220px;" placeholder="Lý do (VD: hư hỏng đồ...)" required>
                            <button type="submit" class="btn btn-outline btn-sm">➕ Thêm phụ phí phát sinh</button>
                        </form>

                        <form method="POST" action="{{ route('admin.bookings.extend-stay.store', $booking->id) }}" id="extend-stay-form" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;"
                              onsubmit="return confirm('Xác nhận gia hạn tới ngày ' + document.getElementById('extend-checkout').value + '?')">
                            @csrf
                            <input type="date" name="new_check_out" id="extend-checkout" class="input" style="width:auto;" min="{{ $booking->check_out->copy()->addDay()->toDateString() }}" required>
                            <span id="extend-preview" style="font-size: 12px; color: #64748b;"></span>
                            <button type="submit" class="btn btn-outline btn-sm">📅 Gia hạn thời gian thuê phòng</button>
                        </form>
                    </div>
                @endif

                <div class="action-row" style="margin-top: 16px;">
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
            @else
                <div class="empty-box">Đơn này chưa có thông tin thanh toán.</div>
            @endif
        </div>
    </div>

    @include('bookings._activity-log', [
        'booking'   => $booking,
        'timeline'  => $timeline,
        'chatRoute' => $booking->user_id ? route('admin.chat.show', $booking->user_id) : null,
    ])
</div>

@if ($booking->status === \App\Enums\BookingStatus::CHECKED_IN)
    @push('scripts')
        <script>
            (function () {
                const extendInput   = document.getElementById('extend-checkout');
                const extendPreview = document.getElementById('extend-preview');
                const previewUrl    = '{{ route('admin.bookings.extend-stay.preview', $booking->id) }}';

                extendInput?.addEventListener('change', async () => {
                    if (! extendInput.value) return;

                    extendPreview.style.color = '';
                    extendPreview.textContent = 'Đang kiểm tra phòng trống...';

                    try {
                        const res  = await fetch(`${previewUrl}?new_check_out=${extendInput.value}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const data = await res.json();

                        if (data.ok) {
                            extendPreview.textContent = `Thêm ${data.nights_added} đêm — phí thêm: ${Number(data.extra_amount).toLocaleString('vi-VN')}đ`;
                        } else {
                            extendPreview.style.color = '#dc2626';
                            extendPreview.textContent = data.message;
                        }
                    } catch (e) {
                        extendPreview.style.color = '#dc2626';
                        extendPreview.textContent = 'Không kiểm tra được, vui lòng thử lại.';
                    }
                });
            })();
        </script>
    @endpush
@endif
@endsection
