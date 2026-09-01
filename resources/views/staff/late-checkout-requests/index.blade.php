@extends('layouts.staff')

@section('title', 'Yêu cầu trả phòng muộn · Homi Staff')
@section('page_title', 'Yêu cầu trả phòng muộn')
@section('page_subtitle', 'Khách đang lưu trú gửi yêu cầu trả phòng muộn hơn giờ chuẩn — duyệt hoặc từ chối.')

@section('content')
<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Trả phòng muộn đang áp dụng</div>
    <h2 class="section-title" style="font-size: 18px;">Phòng nào đang trả phòng muộn trong ngày</h2>

    <form method="GET" class="filter-bar">
        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
        <input type="date" name="date" value="{{ $usageDate }}" onchange="this.form.submit()">
        <button type="submit" class="btn btn-outline btn-sm">Xem</button>
    </form>

    @if ($usage->isEmpty())
        <div class="empty-box">Không có phòng nào trả phòng muộn trong ngày {{ \Carbon\Carbon::parse($usageDate)->format('d/m/Y') }}.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Phòng</th>
                        <th>Loại phòng</th>
                        <th>Đơn</th>
                        <th>Khách</th>
                        <th>Giờ trả muộn</th>
                        <th>Phụ phí</th>
                        <th>Trạng thái đơn</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usage as $row)
                        <tr>
                            <td>{{ $row['bookingItemRoom']->room->room_number }}</td>
                            <td>{{ $row['bookingItemRoom']->bookingItem->roomType->name ?? '—' }}</td>
                            <td>{{ $row['booking']->booking_code }}</td>
                            <td>{{ $row['booking']->customer_name }}</td>
                            <td>{{ substr($row['request']->requested_checkout_time, 0, 5) }}</td>
                            <td>{{ number_format($row['request']->fee_amount, 0, ',', '.') }}đ</td>
                            <td><span class="badge {{ $row['booking']->status->badgeClass() }}">{{ $row['booking']->status->label() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="hidden" name="date" value="{{ $usageDate }}">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chờ duyệt</option>
            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Đã duyệt</option>
            <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Đã từ chối</option>
        </select>
    </form>

    @if ($requests->isEmpty())
        <div class="empty-box">Chưa có yêu cầu trả phòng muộn nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Đơn</th>
                        <th>Khách</th>
                        <th>Giờ yêu cầu</th>
                        <th>Số giờ trễ</th>
                        <th>Phụ phí</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <td>{{ $request->booking->booking_code }}</td>
                            <td>{{ $request->user->name }}</td>
                            <td>{{ substr($request->requested_checkout_time, 0, 5) }}</td>
                            <td>{{ $request->hours_late }} giờ</td>
                            <td>{{ number_format($request->fee_amount, 0, ',', '.') }}đ</td>
                            <td>
                                @php
                                    $statusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$request->status] ?? 'badge-green';
                                    $statusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$request->status] ?? $request->status;
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <a href="{{ route('staff.late-checkout-requests.show', $request->id) }}" class="btn btn-outline btn-sm">Xem</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">{{ $requests->links() }}</div>
    @endif
</div>
@endsection
