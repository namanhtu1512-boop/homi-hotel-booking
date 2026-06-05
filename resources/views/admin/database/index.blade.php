@extends('layouts.app')

@section('title', 'Database Homi')
@section('banner_tag', 'Database Overview')
@section('banner_title', 'Database cơ bản')
@section('banner_subtitle', 'Theo dõi nhanh các bảng dữ liệu chính của hệ thống như users, hotels, room_types, bookings, booking_items và payments.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div>
            <div class="section-kicker">Quản trị dữ liệu</div>
            <h2 class="section-title" style="margin-bottom: 6px;">Tổng quan dữ liệu</h2>
            <p class="section-desc">Danh sách bảng và số lượng bản ghi hiện có trong hệ thống.</p>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-outline">Quay lại dashboard</a>
    </div>

    <div class="db-summary">
        @foreach ($database as $tableName => $table)
            <div class="db-summary-card">
                <div class="name">{{ $tableName }}</div>
                <div class="value">{{ $table['count'] }}</div>
            </div>
        @endforeach
    </div>
</div>

@foreach ($database as $tableName => $table)
    <div class="card">
        <div class="table-section-head">
            <div>
                <div class="section-kicker">Bảng dữ liệu</div>
                <h2 class="section-title" style="margin-bottom: 6px; text-transform: capitalize;">{{ $tableName }}</h2>
                <p class="section-desc">Hiển thị tối đa 20 dòng dữ liệu mới nhất.</p>
            </div>

            <div class="table-count">
                {{ $table['count'] }} dòng
            </div>
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

                                        if ($column === 'password' && !empty($rawValue)) {
                                            $displayValue = '********';
                                        } elseif (is_null($rawValue) || $rawValue === '') {
                                            $displayValue = 'Trống';
                                        } else {
                                            $displayValue = \Illuminate\Support\Str::limit((string) $rawValue, 80);
                                        }
                                    @endphp

                                    <td title="{{ is_null($rawValue) ? '' : (string) $rawValue }}">
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