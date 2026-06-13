<?php

use App\Models\Amenity;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('runs migrate fresh without error and creates expected tables', function () {
    // RefreshDatabase đã migrate, kiểm tra các bảng cốt lõi tồn tại
    $tables = [
        'users', 'amenities', 'hotels', 'hotel_images', 'hotel_amenity',
        'room_types', 'room_type_images',
        'bookings', 'booking_items', 'payments',
    ];

    foreach ($tables as $table) {
        expect(\Illuminate\Support\Facades\Schema::hasTable($table))
            ->toBeTrue("Bảng '{$table}' chưa được tạo");
    }

    // Ghi chú: 'room_type_amenity' và 'booking_status_logs' thuộc phạm vi
    // tuần 6 và tuần 12 (BE2/BE3), sẽ được bổ sung vào danh sách này khi
    // migration tương ứng được tạo.
});

it('runs db:seed without error and creates demo data', function () {
    Artisan::call('db:seed');

    expect(User::count())->toBeGreaterThanOrEqual(4);
    expect(User::where('role', 'admin')->count())->toBeGreaterThanOrEqual(1);
    expect(User::where('role', 'staff')->count())->toBeGreaterThanOrEqual(1);
    expect(User::where('status', 'locked')->count())->toBeGreaterThanOrEqual(1);

    expect(Amenity::count())->toBeGreaterThan(0);
    expect(Hotel::count())->toBeGreaterThan(0);
    expect(RoomType::count())->toBeGreaterThan(0);
});

it('ensures hotel and room_type relationships work after seeding', function () {
    Artisan::call('db:seed');

    $hotel = Hotel::with('roomTypes', 'amenities', 'images')->first();

    expect($hotel)->not->toBeNull();
    expect($hotel->roomTypes)->not->toBeEmpty();
    expect($hotel->amenities->count())->toBeGreaterThan(0);
});