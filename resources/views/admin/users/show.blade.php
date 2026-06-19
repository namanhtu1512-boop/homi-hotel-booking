@extends('layouts.admin')

@section('title', 'Hồ sơ ' . $user->name . ' · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>👤 Hồ sơ người dùng</h1><p>Thông tin chi tiết tài khoản</p></div>
        <div class="admin-page-actions"><a href="{{ route('admin.users.index') }}" class="btn btn-outline">← Quay lại danh sách</a></div>
    </div>

    <div class="data-card">
        <div style="padding:1.4rem">
            <div class="flex-center" style="gap:1rem;margin-bottom:1.25rem">
                <div class="avatar-sm" style="width:56px;height:56px;font-size:1.4rem">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div><div style="font-weight:700;font-size:1.05rem">{{ $user->name }}</div><div class="text-muted" style="font-size:.85rem">{{ $user->email }}</div></div>
            </div>
            <div class="info-grid">
                <div class="info-item"><label>Vai trò</label><p>
                    <span class="badge {{ $user->role === 'admin' ? 'badge-blue' : ($user->role === 'staff' ? 'badge-pending' : 'badge-confirmed') }}">
                        {{ ['admin' => 'Quản trị viên', 'staff' => 'Nhân viên', 'customer' => 'Khách hàng'][$user->role] ?? $user->role }}
                    </span>
                </p></div>
                <div class="info-item"><label>Trạng thái</label><p><span class="badge {{ $user->status === 'active' ? 'badge-confirmed' : 'badge-cancelled' }}">{{ $user->status === 'active' ? 'Hoạt động' : 'Đã khóa' }}</span></p></div>
                <div class="info-item"><label>Số điện thoại</label><p>{{ $user->phone ?? '—' }}</p></div>
                <div class="info-item"><label>Địa chỉ</label><p>{{ $user->address ?? '—' }}</p></div>
                <div class="info-item"><label>Số đặt phòng</label><p>{{ $user->bookings_count }}</p></div>
                <div class="info-item"><label>Ngày tham gia</label><p>{{ $user->created_at->format('d/m/Y') }}</p></div>
            </div>

            @if ($user->id !== auth()->id() && auth()->user()->role === 'admin')
                <hr class="divider">
                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" onsubmit="return confirm('{{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }} tài khoản này?');">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn {{ $user->status === 'active' ? 'btn-danger' : 'btn-primary' }}">
                        {{ $user->status === 'active' ? '🔒 Khóa tài khoản' : '🔓 Mở khóa tài khoản' }}
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
