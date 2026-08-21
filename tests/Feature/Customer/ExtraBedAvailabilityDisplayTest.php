<?php

namespace Tests\Feature\Customer;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\ExtraBedRequest;
use App\Models\HotelInfo;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtraBedAvailabilityDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsCustomer(): User
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer);

        return $customer;
    }

    public function test_trang_dat_phong_hien_so_giuong_phu_con_trong_khi_da_chon_ngay(): void
    {
        $this->loginAsCustomer();
        HotelInfo::create(['name' => 'Homi', 'address' => 'Test', 'status' => 'active', 'extra_beds_total' => 5]);

        $response = $this->get(route('customer.bookings.create', [
            'check_in'  => '2026-12-12',
            'check_out' => '2026-12-15',
        ]));

        $response->assertOk();
        $response->assertSee('còn 5 giường phụ trống trong khoảng ngày đã chọn');
    }

    public function test_trang_dat_phong_khong_hien_so_giuong_phu_khi_chua_chon_ngay(): void
    {
        $this->loginAsCustomer();

        $response = $this->get(route('customer.bookings.create'));

        $response->assertOk();
        $response->assertDontSee('giường phụ trống trong khoảng ngày đã chọn');
    }

    public function test_trang_dat_phong_tru_dung_so_giuong_phu_da_dung_boi_don_khac(): void
    {
        $this->loginAsCustomer();
        HotelInfo::create(['name' => 'Homi', 'address' => 'Test', 'status' => 'active', 'extra_beds_total' => 5]);

        $roomType = RoomType::factory()->create();
        $otherBooking = Booking::factory()->create([
            'status'    => BookingStatus::CONFIRMED,
            'check_in'  => '2026-12-10',
            'check_out' => '2026-12-20',
        ]);
        BookingItem::factory()->create([
            'booking_id'   => $otherBooking->id,
            'room_type_id' => $roomType->id,
            'extra_beds'   => 3,
        ]);

        $response = $this->get(route('customer.bookings.create', [
            'check_in'  => '2026-12-12',
            'check_out' => '2026-12-15',
        ]));

        $response->assertOk();
        $response->assertSee('còn 2 giường phụ trống trong khoảng ngày đã chọn');
    }

    public function test_trang_chi_tiet_don_cho_tu_van_hien_ro_so_giuong_phu_con_trong(): void
    {
        $customer = $this->loginAsCustomer();

        $booking = Booking::factory()->create([
            'user_id'   => $customer->id,
            'status'    => BookingStatus::PENDING_CONSULTATION,
            'check_in'  => '2026-12-22',
            'check_out' => '2026-12-25',
        ]);

        ExtraBedRequest::create([
            'booking_id'            => $booking->id,
            'requested_extra_beds'  => 3,
            'available_extra_beds'  => 2,
            'status'                => 'pending',
        ]);

        $response = $this->get(route('customer.bookings.show', $booking->id));

        $response->assertOk();
        $response->assertSee('Hiện chỉ còn', false);
        $response->assertSee('2', false);
        $response->assertSee('giường phụ trống vào ngày 22/12/2026', false);
        $response->assertSee('bạn cần 3, thiếu 1', false);
    }
}
