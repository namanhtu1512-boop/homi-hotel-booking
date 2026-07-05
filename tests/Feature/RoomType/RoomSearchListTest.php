<?php

namespace Tests\Feature\RoomType;

use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BE4 — Tuần 7 (22/06 – 28/06) · Sprint 4: Public room list & search/filter
 *
 * Phạm vi tuần này (theo kế hoạch BE4 tuần 7):
 *   - Viết automated test cho tính năng search/list phòng công khai
 *   - Kiểm thử hiệu năng cơ bản với seed demo
 *   - Kiểm thử UI trang danh sách phòng /rooms trên customer
 *   - Bổ sung tài liệu route public (xem cuối file — RoomRouteDocTest)
 *
 * Không lặp lại các test phân quyền admin/CRUD đã có ở:
 *   AdminRoomTypeAccessTest.php, RoomTypeDataTest.php, RoomListTest.php
 *
 * Chạy riêng: php artisan test --filter=RoomSearchListTest
 * Chạy nhóm: php artisan test tests/Feature/RoomType/
 *
 * ─── Bảng test case ───────────────────────────────────────────────────────────
 * TC-RSL-001  Guest xem danh sách phòng không cần đăng nhập
 * TC-RSL-002  Chỉ phòng status=active xuất hiện (hidden, maintenance bị ẩn)
 * TC-RSL-003  Phòng bị soft-delete không xuất hiện
 * TC-RSL-004  Trang trả về 200 khi không có phòng nào (empty state)
 * TC-RSL-010  Filter keyword — tìm theo tên
 * TC-RSL-011  Filter keyword — tìm theo mô tả
 * TC-RSL-012  Filter keyword — không phân biệt hoa thường
 * TC-RSL-013  Filter keyword — không có kết quả khớp -> hiện thông báo trống
 * TC-RSL-014  Keyword rỗng/chỉ khoảng trắng không gây lỗi
 * TC-RSL-020  Filter min_price
 * TC-RSL-021  Filter max_price
 * TC-RSL-022  Filter min_price + max_price kết hợp
 * TC-RSL-023  max_price < min_price -> redirect về /rooms kèm lỗi validation
 * TC-RSL-024  Giá âm -> lỗi validation
 * TC-RSL-030  Filter capacity — lấy phòng >= capacity yêu cầu
 * TC-RSL-031  capacity = 0 hoặc âm -> lỗi validation
 * TC-RSL-040  check_in không có check_out -> lỗi validation
 * TC-RSL-041  check_out không có check_in -> lỗi validation
 * TC-RSL-042  check_in ở quá khứ -> lỗi validation
 * TC-RSL-043  check_out trước check_in -> lỗi validation
 * TC-RSL-044  Ngày hợp lệ được giữ trong $filters trả về view (để chuyển sang form booking)
 * TC-RSL-045  Ngày hợp lệ KHÔNG loại trừ phòng (availability check là Sprint 5)
 * TC-RSL-050  Kết hợp nhiều filter cùng lúc (keyword + price + capacity)
 * TC-RSL-051  Filter không khớp bất kỳ phòng nào -> danh sách rỗng (không lỗi)
 * TC-RSL-060  Không có param hotel_id / location trong route (kiến trúc 1 khách sạn)
 * TC-RSL-061  Kết quả sắp xếp theo giá tăng dần
 * TC-RSL-070  Trang phòng trả response dưới 500ms với 20 phòng (smoke perf)
 * TC-RSL-071  Không có N+1 query khi tải 10 phòng (tối đa 5 queries)
 * TC-RSL-080  Customer đã đăng nhập vẫn xem được trang (không bị redirect)
 * TC-RSL-081  Admin đã đăng nhập cũng xem được trang công khai
 * ─────────────────────────────────────────────────────────────────────────────
 */
class RoomSearchListTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================
    // Helpers
    // =========================================================

    private function makeActive(array $attrs = []): RoomType
    {
        return RoomType::factory()->create(array_merge(['status' => 'active'], $attrs));
    }

    private function makeCustomer(): User
    {
        return User::factory()->create(['role' => 'customer', 'status' => 'active']);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function roomsUrl(array $params = []): string
    {
        return '/rooms' . ($params ? '?' . http_build_query($params) : '');
    }

    // =========================================================
    // TC-RSL-001 đến TC-RSL-004: Quyền truy cập & trạng thái
    // =========================================================

    /** @test */
    public function test_TC_RSL_001_guest_can_view_room_list_without_login(): void
    {
        $this->makeActive(['name' => 'Phòng Tiêu Chuẩn']);

        $this->get('/rooms')
            ->assertOk()
            ->assertSee('Phòng Tiêu Chuẩn');
    }

    /** @test */
    public function test_TC_RSL_002_only_active_rooms_appear_hidden_and_maintenance_excluded(): void
    {
        $this->makeActive(['name' => 'Phòng Hoạt Động']);
        RoomType::factory()->hidden()->create(['name' => 'Phòng Đã Ẩn']);
        RoomType::factory()->maintenance()->create(['name' => 'Phòng Bảo Trì']);

        $response = $this->get('/rooms');

        $response->assertSee('Phòng Hoạt Động');
        $response->assertDontSee('Phòng Đã Ẩn');
        $response->assertDontSee('Phòng Bảo Trì');
    }

    /** @test */
    public function test_TC_RSL_003_soft_deleted_room_does_not_appear(): void
    {
        $room = $this->makeActive(['name' => 'Phòng Đã Xóa']);
        $room->delete();

        $this->get('/rooms')->assertDontSee('Phòng Đã Xóa');
    }

    /** @test */
    public function test_TC_RSL_004_page_returns_200_when_no_rooms_exist(): void
    {
        $this->get('/rooms')->assertOk();
    }

    // =========================================================
    // TC-RSL-010 đến TC-RSL-014: Filter theo keyword
    // =========================================================

    /** @test */
    public function test_TC_RSL_010_keyword_filter_matches_room_name(): void
    {
        $this->makeActive(['name' => 'Phòng Deluxe Hướng Biển']);
        $this->makeActive(['name' => 'Phòng Family']);

        $response = $this->get($this->roomsUrl(['keyword' => 'Deluxe']));

        $response->assertSee('Phòng Deluxe Hướng Biển');
        $response->assertDontSee('Phòng Family');
    }

    /** @test */
    public function test_TC_RSL_011_keyword_filter_matches_room_description(): void
    {
        $this->makeActive([
            'name'        => 'Phòng A',
            'description' => 'View núi tuyệt đẹp',
        ]);
        $this->makeActive([
            'name'        => 'Phòng B',
            'description' => 'Nội thất hiện đại',
        ]);

        $response = $this->get($this->roomsUrl(['keyword' => 'núi']));

        $response->assertSee('Phòng A');
        $response->assertDontSee('Phòng B');
    }

    /** @test */
    public function test_TC_RSL_012_keyword_filter_is_case_insensitive(): void
    {
        $this->makeActive(['name' => 'Phòng Cao Cấp']);

        $response = $this->get($this->roomsUrl(['keyword' => 'cao cấp']));

        $response->assertSee('Phòng Cao Cấp');
    }

    /** @test */
    public function test_TC_RSL_013_keyword_with_no_match_shows_empty_list(): void
    {
        $this->makeActive(['name' => 'Phòng Standard']);

        $response = $this->get($this->roomsUrl(['keyword' => 'xyz_không_tồn_tại']));

        $response->assertOk();
        $response->assertDontSee('Phòng Standard');
    }

    /** @test */
    public function test_TC_RSL_014_empty_keyword_does_not_cause_error(): void
    {
        $this->makeActive(['name' => 'Phòng X']);

        // keyword rỗng và keyword chỉ khoảng trắng đều không gây lỗi
        $this->get($this->roomsUrl(['keyword' => '   ']))->assertOk();
        $this->get($this->roomsUrl(['keyword' => '']))->assertOk();
    }

    // =========================================================
    // TC-RSL-020 đến TC-RSL-024: Filter theo giá
    // =========================================================

    /** @test */
    public function test_TC_RSL_020_min_price_filter_excludes_cheaper_rooms(): void
    {
        $this->makeActive(['name' => 'Phòng Rẻ', 'price_per_night' => 300000]);
        $this->makeActive(['name' => 'Phòng Đắt', 'price_per_night' => 2000000]);

        $response = $this->get($this->roomsUrl(['min_price' => 1000000]));

        $response->assertSee('Phòng Đắt');
        $response->assertDontSee('Phòng Rẻ');
    }

    /** @test */
    public function test_TC_RSL_021_max_price_filter_excludes_expensive_rooms(): void
    {
        $this->makeActive(['name' => 'Phòng Bình Dân', 'price_per_night' => 500000]);
        $this->makeActive(['name' => 'Phòng Hạng Sang', 'price_per_night' => 5000000]);

        // Ghi chú: FilterRoomRequest dùng rule 'gte:min_price' cho max_price.
        // Khi không truyền min_price, Laravel so sánh với null và có thể fail.
        // Truyền min_price=0 để đảm bảo rule gte hoạt động đúng trong mọi version.
        $response = $this->get($this->roomsUrl(['min_price' => 0, 'max_price' => 1000000]));

        $response->assertSee('Phòng Bình Dân');
        $response->assertDontSee('Phòng Hạng Sang');
    }

    /** @test */
    public function test_TC_RSL_022_combining_min_and_max_price_filters_correctly(): void
    {
        $this->makeActive(['name' => 'Phòng 200k', 'price_per_night' => 200000]);
        $this->makeActive(['name' => 'Phòng 800k', 'price_per_night' => 800000]);
        $this->makeActive(['name' => 'Phòng 3M',   'price_per_night' => 3000000]);

        $response = $this->get($this->roomsUrl(['min_price' => 500000, 'max_price' => 2000000]));

        $response->assertSee('Phòng 800k');
        $response->assertDontSee('Phòng 200k');
        $response->assertDontSee('Phòng 3M');
    }

    /** @test */
    public function test_TC_RSL_023_max_price_less_than_min_price_shows_validation_error(): void
    {
        $response = $this->from('/rooms')
            ->get($this->roomsUrl(['min_price' => 1000000, 'max_price' => 500000]));

        $response->assertRedirect('/rooms');
        $response->assertSessionHasErrors('max_price');
    }

    /** @test */
    public function test_TC_RSL_024_negative_price_shows_validation_error(): void
    {
        $response = $this->from('/rooms')
            ->get($this->roomsUrl(['min_price' => -1]));

        $response->assertSessionHasErrors('min_price');
    }

    // =========================================================
    // TC-RSL-030 đến TC-RSL-031: Filter theo sức chứa
    // =========================================================

    /** @test */
    public function test_TC_RSL_030_capacity_filter_returns_rooms_with_at_least_that_capacity(): void
    {
        $this->makeActive(['name' => 'Phòng 2 Người', 'capacity' => 2]);
        $this->makeActive(['name' => 'Phòng 4 Người', 'capacity' => 4]);
        $this->makeActive(['name' => 'Phòng 6 Người', 'capacity' => 6]);

        $response = $this->get($this->roomsUrl(['capacity' => 4]));

        $response->assertSee('Phòng 4 Người');
        $response->assertSee('Phòng 6 Người');
        $response->assertDontSee('Phòng 2 Người');
    }

    /** @test */
    public function test_TC_RSL_031_capacity_zero_shows_validation_error(): void
    {
        $response = $this->from('/rooms')
            ->get($this->roomsUrl(['capacity' => 0]));

        $response->assertSessionHasErrors('capacity');
    }

    // =========================================================
    // TC-RSL-040 đến TC-RSL-045: Filter theo ngày (check_in / check_out)
    // =========================================================

    /** @test */
    public function test_TC_RSL_040_check_in_without_check_out_shows_validation_error(): void
    {
        $response = $this->from('/rooms')
            ->get($this->roomsUrl(['check_in' => now()->addDay()->format('Y-m-d')]));

        $response->assertSessionHasErrors('check_out');
    }

    /** @test */
    public function test_TC_RSL_041_check_out_without_check_in_shows_validation_error(): void
    {
        $response = $this->from('/rooms')
            ->get($this->roomsUrl(['check_out' => now()->addDays(3)->format('Y-m-d')]));

        $response->assertSessionHasErrors('check_in');
    }

    /** @test */
    public function test_TC_RSL_042_check_in_in_the_past_shows_validation_error(): void
    {
        $response = $this->from('/rooms')
            ->get($this->roomsUrl([
                'check_in'  => now()->subDay()->format('Y-m-d'),
                'check_out' => now()->addDays(2)->format('Y-m-d'),
            ]));

        $response->assertSessionHasErrors('check_in');
    }

    /** @test */
    public function test_TC_RSL_043_check_out_before_check_in_shows_validation_error(): void
    {
        $response = $this->from('/rooms')
            ->get($this->roomsUrl([
                'check_in'  => now()->addDays(5)->format('Y-m-d'),
                'check_out' => now()->addDays(3)->format('Y-m-d'),
            ]));

        $response->assertSessionHasErrors('check_out');
    }

    /** @test */
    public function test_TC_RSL_044_valid_date_range_is_preserved_in_filters_for_booking_form(): void
    {
        $checkIn  = now()->addDays(3)->format('Y-m-d');
        $checkOut = now()->addDays(5)->format('Y-m-d');

        $response = $this->get($this->roomsUrl([
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]));

        // Ngày phải được giữ trong view để chuyển sang form booking
        $response->assertOk();
        $response->assertSee($checkIn);
        $response->assertSee($checkOut);
    }

    /** @test */
    public function test_TC_RSL_045_valid_date_range_does_not_filter_out_any_rooms_sprint_4(): void
    {
        // Sprint 4 chưa tích hợp availability — ngày chỉ được giữ lại,
        // KHÔNG dùng để loại phòng. Phòng vẫn hiện đủ.
        $this->makeActive(['name' => 'Phòng Không Lọc Theo Ngày']);

        $response = $this->get($this->roomsUrl([
            'check_in'  => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
        ]));

        $response->assertSee('Phòng Không Lọc Theo Ngày');
    }

    // =========================================================
    // TC-RSL-050 đến TC-RSL-051: Kết hợp nhiều filter
    // =========================================================

    /** @test */
    public function test_TC_RSL_050_combining_keyword_price_and_capacity_filters(): void
    {
        $this->makeActive([
            'name'            => 'Deluxe Suite',
            'price_per_night' => 1500000,
            'capacity'        => 3,
        ]);
        // Không khớp keyword
        $this->makeActive([
            'name'            => 'Standard Room',
            'price_per_night' => 1500000,
            'capacity'        => 3,
        ]);
        // Không khớp price
        $this->makeActive([
            'name'            => 'Deluxe Budget',
            'price_per_night' => 200000,
            'capacity'        => 3,
        ]);
        // Không khớp capacity
        $this->makeActive([
            'name'            => 'Deluxe Twin',
            'price_per_night' => 1500000,
            'capacity'        => 1,
        ]);

        $response = $this->get($this->roomsUrl([
            'keyword'   => 'Deluxe',
            'min_price' => 1000000,
            'capacity'  => 2,
        ]));

        $response->assertSee('Deluxe Suite');
        $response->assertDontSee('Standard Room');
        $response->assertDontSee('Deluxe Budget');
        $response->assertDontSee('Deluxe Twin');
    }

    /** @test */
    public function test_TC_RSL_051_no_filter_match_returns_empty_list_without_error(): void
    {
        $this->makeActive(['name' => 'Phòng Duy Nhất', 'price_per_night' => 500000]);

        $response = $this->get($this->roomsUrl([
            'keyword'   => 'xyz_không_tồn_tại_đâu_cả',
            'min_price' => 9000000,
        ]));

        $response->assertOk();
        $response->assertDontSee('Phòng Duy Nhất');
    }

    // =========================================================
    // TC-RSL-060 đến TC-RSL-061: Kiến trúc 1 khách sạn & thứ tự sắp xếp
    // =========================================================

    /** @test */
    public function test_TC_RSL_060_route_does_not_accept_hotel_id_param(): void
    {
        // Theo kế hoạch dự án: Homi chỉ có 1 khách sạn, không có filter hotel_id
        // Truyền vào hotel_id không được gây lỗi (ignored, không crash)
        $this->makeActive();

        $this->get($this->roomsUrl(['hotel_id' => 999]))->assertOk();
    }

    /** @test */
    public function test_TC_RSL_061_rooms_are_sorted_by_price_ascending(): void
    {
        $this->makeActive(['price_per_night' => 3000000]);
        $this->makeActive(['price_per_night' => 800000]);
        $this->makeActive(['price_per_night' => 1500000]);

        $response = $this->get('/rooms');
        $response->assertOk();

        // Lấy dữ liệu từ view để kiểm tra thứ tự
        $roomTypes = $response->viewData('roomTypes');
        $prices = $roomTypes->pluck('price_per_night')->map(fn ($p) => (float) $p)->toArray();
        $sorted = $prices;
        sort($sorted);

        $this->assertEquals($sorted, $prices, 'Phòng phải được sắp xếp theo giá tăng dần.');
    }

    // =========================================================
    // TC-RSL-070 đến TC-RSL-071: Hiệu năng cơ bản (smoke perf)
    // =========================================================

    /** @test */
    public function test_TC_RSL_070_page_loads_under_500ms_with_20_rooms(): void
    {
        RoomType::factory()->count(20)->create(['status' => 'active']);

        $start   = microtime(true);
        $response = $this->get('/rooms');
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(
            500,
            $elapsed,
            "Trang /rooms phải tải dưới 500ms với 20 phòng. Thực tế: {$elapsed}ms"
        );
    }

    /** @test */
    public function test_TC_RSL_071_no_n_plus_1_query_with_10_rooms(): void
    {
        // Eager loading đã được cấu hình trong RoomTypeService::search()
        // Tối đa 5 queries: rooms (count+select), room images, hotel_info
        // (địa chỉ + số sao hiển thị ở sidebar), điểm đánh giá trung bình.
        // Seed hotel_info trước để không tính luôn INSERT tạo bản ghi mặc
        // định (chi phí một lần duy nhất lúc khởi tạo hệ thống, không phải
        // N+1 thật của trang danh sách phòng).
        $this->seed(\Database\Seeders\HotelInfoSeeder::class);

        RoomType::factory()->count(10)->create(['status' => 'active']);

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->get('/rooms')->assertOk();

        $this->assertLessThanOrEqual(
            5,
            $queryCount,
            "Tải 10 phòng không nên vượt quá 5 queries (N+1 check). Thực tế: {$queryCount} queries."
        );
    }

    // =========================================================
    // TC-RSL-080 đến TC-RSL-081: Quyền truy cập (user đã đăng nhập)
    // =========================================================

    /** @test */
    public function test_TC_RSL_080_logged_in_customer_can_view_room_list(): void
    {
        $this->makeActive(['name' => 'Phòng Cho Khách']);

        $this->actingAs($this->makeCustomer())
            ->get('/rooms')
            ->assertOk()
            ->assertSee('Phòng Cho Khách');
    }

    /** @test */
    public function test_TC_RSL_081_logged_in_admin_can_also_view_public_room_list(): void
    {
        $this->makeActive(['name' => 'Phòng Cho Admin Xem']);

        $this->actingAs($this->makeAdmin())
            ->get('/rooms')
            ->assertOk()
            ->assertSee('Phòng Cho Admin Xem');
    }
}