@extends('layouts.app')

@section('title', 'Đơn ' . $booking->booking_code . ' · Homi')
@section('banner_tag', 'Chi tiết đơn')
@section('banner_title', 'Đơn ' . $booking->booking_code)
@section('banner_subtitle', 'Nhận phòng ' . $booking->check_in->format('d/m/Y') . ' · Trả phòng ' . $booking->check_out->format('d/m/Y'))
@section('banner_badge', $booking->status->label())
@section('banner_badge_class', $booking->status->badgeClass())

@section('content')
<div class="card">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="section-kicker">Phòng đã đặt</div>
            <div class="table-wrapper" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Loại phòng</th>
                            <th>Số lượng</th>
                            <th>Số khách</th>
                            <th>Giá/đêm</th>
                            <th>Số đêm</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($booking->bookingItems as $item)
                            @php
                                $cover = $item->roomType?->images->first();
                            @endphp
                            <tr>
                                <td>
                                    @if ($cover)
                                        <img src="{{ $cover->image_url }}" alt="" style="width: 72px; height: 52px; object-fit: cover; border-radius: 6px;">
                                    @else
                                        <span class="badge">Chưa có ảnh</span>
                                    @endif
                                </td>
                                <td>{{ $item->roomType->name ?? '—' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->adults }} người lớn{{ $item->children ? ', ' . $item->children . ' trẻ em' : '' }}</td>
                                <td>{{ number_format($item->price_per_night, 0, ',', '.') }}đ</td>
                                <td>{{ $item->nights }}</td>
                                <td>{{ number_format($item->subtotal, 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="info-list" style="margin-top: 20px;">
                <div class="info-item">
                    <span class="label">Ngày nhận phòng</span>
                    <span class="value">{{ $booking->check_in->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Ngày trả phòng</span>
                    <span class="value">{{ $booking->check_out->format('d/m/Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Số đêm</span>
                    <span class="value">{{ $booking->nights }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Tổng số khách</span>
                    <span class="value">{{ $booking->adults }} người lớn{{ $booking->children ? ', ' . $booking->children . ' trẻ em' : '' }}</span>
                </div>
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

        <div class="card">
            @if ($booking->payment)
                <div class="section-kicker">Trạng thái</div>
                <div class="info-list" style="margin-top: 10px;">
                    <div class="info-item">
                        <span class="label">Thanh toán</span>
                        <span class="value">
                            <span class="badge {{ $booking->payment->status->badgeClass() }}">
                                {{ $booking->payment->status->label() }}
                            </span>
                        </span>
                    </div>
                    @if ($booking->payment->deposit_paid_at)
                        <div class="info-item">
                            <span class="label">Đã đặt cọc</span>
                            <span class="value">{{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ lúc {{ $booking->payment->deposit_paid_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Còn lại (tiền mặt khi nhận phòng)</span>
                            <span class="value">{{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ</span>
                        </div>
                    @endif
                </div>
            @endif

            <div class="section-kicker" style="margin-top: 22px;">Thông tin liên hệ</div>
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
            </div>

            <div class="quick-actions-row">
                <a href="{{ route('customer.bookings.index') }}" class="btn btn-outline">Quay lại danh sách</a>

                @if ($booking->canCancelByCustomer())
                    <form method="POST" action="{{ route('customer.bookings.cancel', $booking->id) }}"
                        onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn {{ $booking->booking_code }}?');">
                        @csrf
                        <button type="submit" class="btn btn-danger">Hủy đơn</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($booking->payment && $booking->payment->status === \App\Enums\PaymentStatus::PENDING)
            <div class="card">
                <div class="section-kicker">Thanh toán</div>
                <div class="alert alert-success" style="margin-top: 10px;">
                    Đã ghi nhận bạn chuyển khoản — đang chờ khách sạn xác nhận. Vui lòng chờ, không cần thao tác thêm.
                </div>
            </div>
        @elseif ($booking->payment && $booking->payment->status === \App\Enums\PaymentStatus::DEPOSIT_PAID)
            <div class="card">
                <div class="section-kicker">Thanh toán</div>
                <div class="alert alert-success" style="margin-top: 10px;">
                    Đã đặt cọc {{ number_format($booking->payment->deposit_amount, 0, ',', '.') }}đ (30%).
                    Vui lòng thanh toán <strong>{{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ</strong> còn lại bằng tiền mặt khi nhận phòng.
                </div>
            </div>
        @elseif ($booking->canMarkPaymentAsPaid())
            <div class="card">
                <div class="section-kicker">Thanh toán</div>
                <p style="color: var(--muted); font-size: 14px; margin-top: 6px;">
                    Đơn đã được xác nhận. Chọn một trong các hình thức thanh toán bên dưới.
                </p>

                <div style="background: var(--bg); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; margin-top: 12px; font-size: 14px; line-height: 1.8;">
                    <strong>Thông tin chuyển khoản</strong><br>
                    Ngân hàng: Vietcombank — Chi nhánh Cần Thơ<br>
                    Số tài khoản: 0123456789 — Chủ tài khoản: CÔNG TY TNHH HOMI HOTEL<br>
                    Nội dung chuyển khoản: <strong>{{ $booking->booking_code }}</strong>
                </div>

                <div class="quick-actions-row" style="margin-top: 14px;">
                    <form method="POST" action="{{ route('customer.bookings.pay-bank-transfer', $booking->id) }}"
                        onsubmit="return confirm('Xác nhận bạn đã chuyển khoản cho đơn {{ $booking->booking_code }}?');">
                        @csrf
                        <button type="submit" class="btn btn-outline">Tôi đã chuyển khoản</button>
                    </form>

                    @if ($booking->canPayDeposit())
                        <form method="POST" action="{{ route('customer.bookings.pay-deposit', $booking->id) }}"
                            onsubmit="return confirm('Đặt cọc {{ number_format($booking->depositAmount(), 0, ',', '.') }}đ (30%, mô phỏng). Còn lại {{ number_format($booking->remainingAfterDeposit(), 0, ',', '.') }}đ trả bằng tiền mặt khi nhận phòng. Tiếp tục?');">
                            @csrf
                            <button type="submit" class="btn btn-outline">🏦 Đặt cọc 30% — trả tiền mặt khi nhận phòng</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('customer.bookings.pay-online', $booking->id) }}"
                        onsubmit="return confirm('Đây là thanh toán online mô phỏng (demo), không phát sinh giao dịch thật. Tiếp tục?');">
                        @csrf
                        <button type="submit" class="btn btn-primary">💳 Thanh toán ngay (demo)</button>
                    </form>
                </div>
            </div>
        @endif

        @if ($booking->payment && $booking->payment->statusLogs->isNotEmpty())
            <div class="card">
                <div class="section-kicker">Lịch sử thanh toán</div>
                <div class="table-wrapper" style="margin-top: 10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Từ</th>
                                <th>Đến</th>
                                <th>Người thực hiện</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($booking->payment->statusLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $log->from_status?->label() ?? '—' }}</td>
                                    <td>{{ $log->to_status->label() }}</td>
                                    <td>{{ $log->changedBy?->name ?? 'Khách hàng' }}</td>
                                    <td>{{ $log->note ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
@endsection
