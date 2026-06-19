@extends('layouts.admin')

@section('title', 'Database · Homi Admin')

@push('styles')
<style>
    .db-summary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .db-summary-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.1rem; box-shadow: var(--shadow); }
    .db-summary-card .name { color: var(--muted); font-size: .8rem; font-weight: 700; margin-bottom: .4rem; text-transform: capitalize; }
    .db-summary-card .value { font-size: 1.5rem; font-weight: 800; }
</style>
@endpush

@section('content')
    <div class="admin-page-header">
        <div><h1>🗄️ Database</h1><p>Theo dõi nhanh các bảng dữ liệu chính: users, hotels, room_types, bookings, booking_items, payments</p></div>
    </div>

    <div class="db-summary-grid">
        @foreach ($database as $tableName => $table)
            <div class="db-summary-card">
                <div class="name">{{ $tableName }}</div>
                <div class="value">{{ $table['count'] }}</div>
            </div>
        @endforeach
    </div>

    @foreach ($database as $tableName => $table)
        <div class="data-card">
            <div class="data-card-header" style="text-transform:capitalize">
                {{ $tableName }} <span class="count-pill">{{ $table['count'] }} dòng</span>
            </div>
            @if ($table['rows']->isEmpty())
                <div class="empty-state"><div class="icon">🗄️</div><h3>Chưa có dữ liệu</h3><p>Bảng này hiện chưa có bản ghi nào.</p></div>
            @else
                <div class="table-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                @foreach ($table['columns'] as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($table['rows'] as $row)
                                <tr>
                                    @foreach ($table['columns'] as $column)
                                        @php
                                            $rawValue = $row->$column;

                                            if ($column === 'password' && !empty($rawValue)) {
                                                $displayValue = '********';
                                            } elseif (is_null($rawValue) || $rawValue === '') {
                                                $displayValue = 'Trống';
                                            } else {
                                                $displayValue = \Illuminate\Support\Str::limit((string) $rawValue, 80);
                                            }
                                        @endphp
                                        <td title="{{ is_null($rawValue) ? '' : (string) $rawValue }}">{{ $displayValue }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
@endsection
