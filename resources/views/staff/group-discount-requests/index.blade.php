@extends('layouts.staff')

@section('title', 'Ưu đãi đoàn cho khách quen · Homi Nhân viên')
@section('page_title', 'Ưu đãi đoàn cho khách quen')
@section('page_subtitle', 'Tạo đề xuất mã ưu đãi cho khách quen và xem lịch sử đề xuất giảm giá của bạn.')

@section('content')
<div class="card" style="margin-bottom: 20px;">
    <div class="section-kicker">Tạo ưu đãi đoàn</div>
    <h2 class="section-title" style="font-size: 18px;">Đề xuất mã ưu đãi khách quen mới</h2>
    <p class="section-desc">Điền mã và mức giảm giá, gửi đi để admin xem xét — bên Khuyến mãi của admin sẽ nhận được thông báo để đồng ý hoặc từ chối.</p>

    <form method="POST" action="{{ route('staff.promotion-requests.store') }}" class="form-grid">
        @csrf
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label for="code">Mã ưu đãi *</label>
                <input id="code" type="text" name="code" value="{{ old('code') }}" required placeholder="KHACHQUEN10">
            </div>
            <div class="form-group">
                <label for="discount_percent">% giảm giá *</label>
                <input id="discount_percent" type="number" min="0" max="100" step="0.1" name="discount_percent" value="{{ old('discount_percent') }}" required>
            </div>
        </div>
        <div class="form-group">
            <label for="reason">Ghi chú (khách quen nào, lý do đề xuất...)</label>
            <textarea id="reason" name="reason" rows="2">{{ old('reason') }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Gửi đề xuất</button>
    </form>

    @if ($promotionRequests->isEmpty())
        <div class="empty-box" style="margin-top: 16px;">Bạn chưa đề xuất mã ưu đãi khách quen nào.</div>
    @else
        <div class="table-wrapper" style="margin-top: 16px;">
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>% đề xuất</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú của admin</th>
                        <th>Ngày gửi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($promotionRequests as $promoRequest)
                        <tr>
                            <td><span class="badge badge-blue">{{ $promoRequest->code }}</span></td>
                            <td>{{ (float) $promoRequest->discount_percent }}%</td>
                            <td>
                                @php
                                    $promoStatusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$promoRequest->status] ?? 'badge-green';
                                    $promoStatusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$promoRequest->status] ?? $promoRequest->status;
                                @endphp
                                <span class="badge {{ $promoStatusBadge }}">{{ $promoStatusLabel }}</span>
                            </td>
                            <td>{{ $promoRequest->admin_note ?? '—' }}</td>
                            <td>{{ $promoRequest->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">{{ $promotionRequests->links() }}</div>
    @endif
</div>

<div class="card">
    <div class="section-kicker">Lịch sử theo đơn</div>
    <h2 class="section-title" style="font-size: 18px;">Đề xuất giảm giá đoàn/nhóm theo từng đơn</h2>
    <p class="section-desc">Gồm cả mức giảm tự động theo chính sách và các đề xuất giảm thêm bạn gửi từ trang chi tiết đơn.</p>

    <form method="GET" class="filter-bar">
        <select name="status" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chờ duyệt</option>
            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Đã duyệt</option>
            <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Đã từ chối</option>
        </select>
    </form>

    @if ($requests->isEmpty())
        <div class="empty-box">Bạn chưa áp dụng/đề xuất ưu đãi đoàn nào.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Đơn</th>
                        <th>Nguồn</th>
                        <th>Đề xuất</th>
                        <th>Đã duyệt</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <td>{{ $request->booking->booking_code }}</td>
                            <td>
                                <span class="badge {{ $request->type === 'policy_tier' ? 'badge-blue' : 'badge-orange' }}">
                                    {{ $request->type === 'policy_tier' ? 'Tự động theo chính sách' : 'Đề xuất/áp dụng thêm' }}
                                </span>
                            </td>
                            <td>{{ (float) $request->requested_percent }}%</td>
                            <td>{{ $request->approved_percent !== null ? (float) $request->approved_percent . '%' : '—' }}</td>
                            <td>
                                @php
                                    $statusBadge = ['pending' => 'badge-orange', 'approved' => 'badge-green', 'rejected' => 'badge-red'][$request->status] ?? 'badge-green';
                                    $statusLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$request->status] ?? $request->status;
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <a href="{{ route('staff.group-discount-requests.show', $request->id) }}" class="btn btn-outline btn-sm">Xem</a>
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
