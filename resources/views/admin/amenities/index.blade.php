@extends('layouts.admin')

@section('title', 'Quản lý tiện ích · Homi Admin')

@section('content')
    <div class="admin-page-header">
        <div><h1>🧰 Quản lý tiện ích</h1><p>Danh sách tiện ích dùng để gán cho khách sạn</p></div>
        <div class="admin-page-actions"><a href="{{ route('admin.amenities.create') }}" class="btn btn-primary">➕ Thêm tiện ích</a></div>
    </div>

    <div class="data-card">
        <div class="data-card-header">Danh sách tiện ích <span class="count-pill">{{ $amenities->count() }}</span></div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Tiện ích</th><th>Số khách sạn dùng</th><th></th></tr></thead>
                <tbody>
                    @forelse ($amenities as $amenity)
                        <tr>
                            <td><div class="entity-cell"><div class="entity-icon">{{ $amenity->icon ?: '🧰' }}</div><div class="entity-name">{{ $amenity->name }}</div></div></td>
                            <td>{{ $amenity->hotels_count }}</td>
                            <td>
                                <div class="row-actions">
                                    <a class="icon-action" title="Sửa" href="{{ route('admin.amenities.edit', $amenity->id) }}">✏️</a>
                                    <form method="POST" action="{{ route('admin.amenities.destroy', $amenity->id) }}" onsubmit="return confirm('Xóa tiện ích &quot;{{ $amenity->name }}&quot;?{{ $amenity->hotels_count ? ' Sẽ gỡ khỏi '.$amenity->hotels_count.' khách sạn đang dùng.' : '' }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="icon-action danger" title="Xóa">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><div class="empty-state"><div class="icon">🧰</div><h3>Chưa có tiện ích nào</h3></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
