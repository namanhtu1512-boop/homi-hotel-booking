@extends('layouts.admin')

@php
    $isEdit = $room !== null;
@endphp

@section('title', ($isEdit ? 'Sửa phòng' : 'Tạo phòng') . ' · Homi Admin')
@section('page_title', $isEdit ? 'Sửa phòng' : 'Tạo phòng mới')
@section('page_subtitle', 'Các trường có dấu * là bắt buộc.')

@section('content')
<div class="card">
    <div class="page-actions">
        <div></div>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline">Quay lại danh sách</a>
    </div>

    <form method="POST"
        action="{{ $isEdit ? route('admin.rooms.update', $room->id) : route('admin.rooms.store') }}"
        class="form-grid">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="room_type_id">Loại phòng *</label>
            <select id="room_type_id" name="room_type_id" required>
                @foreach ($roomTypes as $roomType)
                    <option value="{{ $roomType->id }}" @selected((string) old('room_type_id', $room->room_type_id ?? '') === (string) $roomType->id)>{{ $roomType->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="room_number">Số phòng *</label>
            <input id="room_number" type="text" name="room_number" value="{{ old('room_number', $room->room_number ?? '') }}" required placeholder="VD: 101">
        </div>

        <button type="submit" class="btn btn-primary btn-block">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo phòng' }}</button>
    </form>
</div>
@endsection
