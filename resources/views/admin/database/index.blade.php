@extends('layouts.admin')

@section('title', 'Database · Homi Admin')
@section('page_title', 'Database cơ bản')
@section('page_subtitle', 'Theo dõi nhanh các bảng dữ liệu chính: users, hotel_info, room_types, bookings, booking_items, payments.')

@section('content')
<div class="card">
    <div class="section-kicker">Quản trị dữ liệu</div>
    <h2 class="section-title">Tổng quan dữ liệu</h2>
    <p class="section-desc">Danh sách bảng và số lượng bản ghi hiện có trong hệ thống.</p>

    <div class="stats-grid" style="margin-top: 16px;">
        @foreach ($database as $tableName => $table)
            <div class="stat-card">
                <div class="stat-label">{{ $tableName }}</div>
                <div class="stat-value">{{ $table['count'] }}</div>
            </div>
        @endforeach
    </div>
</div>

@foreach ($database as $tableName => $table)
    <div class="card">
        <div class="page-actions">
            <div>
                <div class="section-kicker">Bảng dữ liệu</div>
                <h2 class="section-title" style="text-transform: capitalize;">{{ $tableName }}</h2>
                <p class="section-desc">Hiển thị tối đa 20 dòng dữ liệu mới nhất.</p>
            </div>
            <span class="badge badge-blue">{{ $table['count'] }} dòng</span>
        </div>

        @if ($table['rows']->isEmpty())
            <div class="empty-box">Chưa có dữ liệu trong bảng này.</div>
        @else
            <div class="table-wrapper">
                <table>
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

                                        $displayValue = (is_null($rawValue) || $rawValue === '')
                                            ? 'Trống'
                                            : \Illuminate\Support\Str::limit((string) $rawValue, 80);
                                    @endphp

                                    <td title="{{ $displayValue === 'Trống' ? '' : $displayValue }}">
                                        {{ $displayValue }}
                                    </td>
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
