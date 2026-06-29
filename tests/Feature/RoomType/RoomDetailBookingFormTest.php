<?php

namespace Tests\Feature\RoomType;

use App\Models\HotelInfo;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tuần 8 - Sprint 4: Test luồng khách hàng xem chi tiết phòng và form đặt phòng.
 *
 * Bao gồm:
 *  - Room detail /rooms/{id}: hiển thị đủ thông tin, phòng inactive/không tồn tại → 404
 *  - Dữ liệu thiếu ảnh, thiếu tiện ích không làm vỡ trang
 *  - Thông tin chính sách khách sạn hiển thị trên trang detail
 *  - Luồng danh sách → detail → booking form liền mạch
 *  - Booking form /customer/bookings/create: accessible với customer, redirect login khi chưa đăng nhập
 *  - Booking form pre-fill đúng tham số từ room detail
 */
class RoomDetailBookingFormTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Room detail — hiển thị cơ bản
    // ----------------------------------------------------------------

    public function test_guest_can_view_active_room_detail(): void
    {
        $room = RoomType::factory()->create([
            'name'            => 'Phòng Deluxe Test',
            'description'     => 'Phòng view đẹp, nội thất sang trọng.',
            'price_per_night' => 1500000,
            'capacity'        => 2,
            'bed_type'        => '1 giường đôi lớn',
            'area'            => 35,
        ]);

        $response = $this->get("/rooms/{$room->id}");

        $response->assertOk();
        $response->assertSee('Phòng Deluxe Test');
        $response->assertSee('Phòng view đẹp');
        $response->assertSee('1.500.000');
        $response->assertSee('2 khách');
        $response->assertSee('1 giường đôi lớn');
        $response->assertSee('35');
    }

    public function test_room_detail_shows_all_room_images(): void
    {
        $room = RoomType::factory()->create();
        RoomTypeImage::factory()->count(3)->create(['room_type_id' => $room->id]);

        $response = $this->get("/rooms/{$room->id}");

        $response->assertOk();
        // Gallery partial được render (div.hotel-gallery-main phải có)
        $response->assertSee('hotel-gallery-main', false);
    }

    public function test_room_detail_without_images_does_not_crash(): void
    {
        $room = RoomType::factory()->create();

        // Không gắn ảnh nào — trang phải vẫn load được
        $response = $this->get("/rooms/{$room->id}");

        $response->assertOk();
        $response->assertSee('Chưa có ảnh');
    }

    public function test_room_detail_without_description_shows_placeholder(): void
    {
        $room = RoomType::factory()->create(['description' => null]);

        $response = $this->get("/rooms/{$room->id}");

        $response->assertOk();
        $response->assertSee('Chưa có mô tả chi tiết');
    }

    // ----------------------------------------------------------------
    // Room detail — phòng inactive / không tồn tại → 404
    // ----------------------------------------------------------------

    public function test_hidden_room_detail_returns_404(): void
    {
        $room = RoomType::factory()->hidden()->create();

        $this->get("/rooms/{$room->id}")->assertNotFound();
    }

    public function test_maintenance_room_detail_returns_404(): void
    {
        $room = RoomType::factory()->maintenance()->create();

        $this->get("/rooms/{$room->id}")->assertNotFound();
    }

    public function test_soft_deleted_room_detail_returns_404(): void
    {
        $room = RoomType::factory()->create();
        $room->delete();

        $this->get("/rooms/{$room->id}")->assertNotFound();
    }

    public function test_nonexistent_room_detail_returns_404(): void
    {
        $this->get('/rooms/99999')->assertNotFound();
    }

    // ----------------------------------------------------------------
    // Room detail — chính sách khách sạn từ HotelInfo
    // ----------------------------------------------------------------

    public function test_room_detail_shows_hotel_checkin_checkout_policy(): void
    {
        $hotel = HotelInfo::instance();
        $hotel->update([
            'check_in_time'  => '14:00',
            'check_out_time' => '12:00',
            'policies'       => 'Không hút thuốc trong phòng.',
        ]);

        $room = RoomType::factory()->create();

        $response = $this->get("/rooms/{$room->id}");

        $response->assertOk();
        $response->assertSee('14:00');
        $response->assertSee('12:00');
        $response->assertSee('Không hút thuốc trong phòng.');
    }

    public function test_room_detail_shows_hotel_name_and_address(): void
    {
        $hotel = HotelInfo::instance();
        $hotel->update(['name' => 'Homi Hotel Đà Nẵng', 'address' => '123 Bạch Đằng']);

        $room = RoomType::factory()->create();

        $response = $this->get("/rooms/{$room->id}");

        $response->assertOk();
        $response->assertSee('Homi Hotel Đà Nẵng');
        $response->assertSee('123 Bạch Đằng');
    }

    // ----------------------------------------------------------------
    // Luồng danh sách → detail: link từ list đến detail hoạt động đúng
    // ----------------------------------------------------------------

    public function test_room_list_contains_link_to_room_detail(): void
    {
        $room = RoomType::factory()->create(['name' => 'Phòng Link Test']);

        $response = $this->get('/rooms');

        $response->assertOk();
        $response->assertSee('Phòng Link Test');
        $response->assertSee("/rooms/{$room->id}", false);
    }

    public function test_room_detail_check_in_check_out_params_are_preserved(): void
    {
        $room = RoomType::factory()->create();
        $checkIn  = now()->addDays(3)->format('Y-m-d');
        $checkOut = now()->addDays(6)->format('Y-m-d');

        $response = $this->get("/rooms/{$room->id}?check_in={$checkIn}&check_out={$checkOut}&quantity=2");

        $response->assertOk();
        $response->assertSee($checkIn, false);
        $response->assertSee($checkOut, false);
    }

    // ----------------------------------------------------------------
    // Booking form — kiểm soát truy cập
    // ----------------------------------------------------------------

    public function test_guest_is_redirected_to_login_when_accessing_booking_form(): void
    {
        $this->get('/customer/bookings/create')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_customer_can_access_booking_form(): void
    {
        $customer = User::factory()->customer()->create();
        $room     = RoomType::factory()->create();

        $response = $this->actingAs($customer)
            ->get("/customer/bookings/create?room_type_id={$room->id}");

        $response->assertOk();
        $response->assertSee($room->name);
    }

    public function test_booking_form_shows_room_price_per_night(): void
    {
        $customer = User::factory()->customer()->create();
        $room     = RoomType::factory()->create(['price_per_night' => 1200000]);

        $response = $this->actingAs($customer)
            ->get("/customer/bookings/create?room_type_id={$room->id}");

        $response->assertOk();
        $response->assertSee('1.200.000');
    }

    public function test_booking_form_prefills_dates_from_query_params(): void
    {
        $customer = User::factory()->customer()->create();
        $room     = RoomType::factory()->create();
        $checkIn  = now()->addDays(5)->format('Y-m-d');
        $checkOut = now()->addDays(8)->format('Y-m-d');

        $response = $this->actingAs($customer)->get(
            "/customer/bookings/create?room_type_id={$room->id}&check_in={$checkIn}&check_out={$checkOut}&quantity=2"
        );

        $response->assertOk();
        $response->assertSee($checkIn, false);
        $response->assertSee($checkOut, false);
        // quantity=2
        $response->assertSee('value="2"', false);
    }

    public function test_booking_form_prefills_customer_contact_info(): void
    {
        $customer = User::factory()->customer()->create([
            'name'  => 'Nguyễn Văn Test',
            'email' => 'test@example.com',
        ]);
        $room = RoomType::factory()->create();

        $response = $this->actingAs($customer)
            ->get("/customer/bookings/create?room_type_id={$room->id}");

        $response->assertOk();
        $response->assertSee('Nguyễn Văn Test');
        $response->assertSee('test@example.com');
    }

    public function test_booking_form_without_room_type_shows_room_select(): void
    {
        $customer = User::factory()->customer()->create();
        RoomType::factory()->count(3)->create();

        $response = $this->actingAs($customer)
            ->get('/customer/bookings/create');

        $response->assertOk();
        // Select dropdown phải xuất hiện khi không truyền room_type_id
        $response->assertSee('Chọn loại phòng');
    }

    public function test_booking_form_with_inactive_room_type_returns_404(): void
    {
        $customer = User::factory()->customer()->create();
        $room     = RoomType::factory()->hidden()->create();

        $this->actingAs($customer)
            ->get("/customer/bookings/create?room_type_id={$room->id}")
            ->assertNotFound();
    }

    public function test_booking_form_shows_required_fields(): void
    {
        $customer = User::factory()->customer()->create();
        $room     = RoomType::factory()->create();

        $response = $this->actingAs($customer)
            ->get("/customer/bookings/create?room_type_id={$room->id}");

        $response->assertOk();
        $response->assertSee('Ngày nhận phòng');
        $response->assertSee('Ngày trả phòng');
        $response->assertSee('Số phòng');
        $response->assertSee('Họ tên khách');
        $response->assertSee('Số điện thoại');
    }

    // ----------------------------------------------------------------
    // Luồng hoàn chỉnh: detail → booking form (link đúng route)
    // ----------------------------------------------------------------

    public function test_room_detail_booking_link_uses_correct_route(): void
    {
        $room     = RoomType::factory()->create(['total_rooms' => 5]);
        $checkIn  = now()->addDays(3)->format('Y-m-d');
        $checkOut = now()->addDays(5)->format('Y-m-d');

        $response = $this->get("/rooms/{$room->id}?check_in={$checkIn}&check_out={$checkOut}&quantity=1");

        $response->assertOk();
        // Sau khi kiểm tra trống thấy còn phòng, link đặt phòng phải trỏ đúng route
        $response->assertSee('customer/bookings/create', false);
    }
}
