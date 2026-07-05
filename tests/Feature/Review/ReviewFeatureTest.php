<?php

namespace Tests\Feature\Review;

use App\Models\Booking;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test đánh giá (US11) — điều kiện đánh giá và quản trị đánh giá.
 *
 * Test case ID | Chức năng                                        | Kết quả mong đợi
 * TC-REV-001   | Đơn completed hiện trong danh sách được đánh giá  | Thấy loại phòng trong /customer/reviews/create
 * TC-REV-002   | Khách gửi đánh giá hợp lệ                         | Tạo review, redirect thành công
 * TC-REV-003   | Không đánh giá được đơn chưa completed            | Lỗi validation
 * TC-REV-004   | Không đánh giá loại phòng không thuộc đơn         | Lỗi validation
 * TC-REV-005   | Không đánh giá trùng (đã đánh giá lần 1)          | Lỗi validation ở lần 2
 * TC-REV-006   | Không đánh giá đơn của người khác                 | 404 (scope theo user_id)
 * TC-REV-007   | Rating trung bình tính đúng                       | AVG khớp DB
 * TC-REV-008   | Admin ẩn/hiện đánh giá                            | status đổi đúng
 * TC-REV-009   | Admin xóa đánh giá                                | Review bị xóa khỏi DB
 * TC-REV-010   | Staff/customer không vào được /admin/reviews      | Redirect đúng dashboard
 */
class ReviewFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function completedBooking(User $customer, RoomType $roomType): Booking
    {
        $booking = Booking::create([
            'booking_code'   => 'REV-' . uniqid(),
            'user_id'        => $customer->id,
            'check_in'       => now()->subDays(10)->format('Y-m-d'),
            'check_out'      => now()->subDays(8)->format('Y-m-d'),
            'nights'         => 2,
            'customer_name'  => $customer->name,
            'customer_phone' => '0900000000',
            'total_amount'   => 2000000,
            'status'         => 'completed',
        ]);

        $booking->bookingItems()->create([
            'room_type_id'    => $roomType->id,
            'quantity'        => 1,
            'price_per_night' => $roomType->price_per_night,
            'nights'          => 2,
            'subtotal'        => 2000000,
        ]);

        return $booking;
    }

    public function test_completed_booking_room_type_appears_in_reviewable_list(): void
    {
        $customer = $this->makeUser('customer');
        $roomType = RoomType::factory()->create();
        $this->completedBooking($customer, $roomType);

        $response = $this->actingAs($customer)->get('/customer/reviews/create');

        $response->assertOk();
        $response->assertSee($roomType->name);
    }

    public function test_customer_can_submit_review(): void
    {
        $customer = $this->makeUser('customer');
        $roomType = RoomType::factory()->create();
        $booking  = $this->completedBooking($customer, $roomType);

        $response = $this->actingAs($customer)->post('/customer/reviews', [
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'rating'       => 5,
            'comment'      => 'Phòng rất đẹp!',
        ]);

        $response->assertRedirect(route('customer.bookings.index'));
        $this->assertDatabaseHas('reviews', [
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'user_id'      => $customer->id,
            'rating'       => 5,
        ]);
    }

    public function test_cannot_review_booking_that_is_not_completed(): void
    {
        $customer = $this->makeUser('customer');
        $roomType = RoomType::factory()->create();
        $booking  = $this->completedBooking($customer, $roomType);
        $booking->update(['status' => 'confirmed']);

        $response = $this->actingAs($customer)->post('/customer/reviews', [
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'rating'       => 5,
        ]);

        $response->assertSessionHasErrors('booking_id');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_cannot_review_room_type_not_in_booking(): void
    {
        $customer      = $this->makeUser('customer');
        $bookedRoom    = RoomType::factory()->create();
        $notBookedRoom = RoomType::factory()->create();
        $booking       = $this->completedBooking($customer, $bookedRoom);

        $response = $this->actingAs($customer)->post('/customer/reviews', [
            'booking_id'   => $booking->id,
            'room_type_id' => $notBookedRoom->id,
            'rating'       => 4,
        ]);

        $response->assertSessionHasErrors('room_type_id');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_cannot_review_same_booking_and_room_type_twice(): void
    {
        $customer = $this->makeUser('customer');
        $roomType = RoomType::factory()->create();
        $booking  = $this->completedBooking($customer, $roomType);

        Review::create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'user_id'      => $customer->id,
            'rating'       => 5,
            'status'       => 'visible',
        ]);

        $response = $this->actingAs($customer)->post('/customer/reviews', [
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'rating'       => 3,
        ]);

        $response->assertSessionHasErrors('room_type_id');
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_customer_cannot_review_another_customers_booking(): void
    {
        $owner    = $this->makeUser('customer');
        $intruder = $this->makeUser('customer');
        $roomType = RoomType::factory()->create();
        $booking  = $this->completedBooking($owner, $roomType);

        $response = $this->actingAs($intruder)->post('/customer/reviews', [
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'rating'       => 5,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_average_rating_matches_database(): void
    {
        $customer = $this->makeUser('customer');
        $roomType = RoomType::factory()->create();

        foreach ([5, 3, 4] as $rating) {
            $booking = $this->completedBooking($customer, $roomType);
            Review::create([
                'booking_id'   => $booking->id,
                'room_type_id' => $roomType->id,
                'user_id'      => $customer->id,
                'rating'       => $rating,
                'status'       => 'visible',
            ]);
        }

        $summary = app(\App\Services\ReviewService::class)->summaryFor($roomType->id);

        $this->assertSame(3, $summary['count']);
        $this->assertSame(4.0, $summary['avg']);
    }

    public function test_admin_can_toggle_review_status(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $roomType = RoomType::factory()->create();
        $booking  = $this->completedBooking($customer, $roomType);

        $review = Review::create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'user_id'      => $customer->id,
            'rating'       => 5,
            'status'       => 'visible',
        ]);

        $this->actingAsAdmin($admin)
            ->patch("/admin/reviews/{$review->id}/toggle")
            ->assertRedirect(route('admin.reviews.index'));

        $this->assertSame('hidden', $review->fresh()->status);
    }

    public function test_admin_can_delete_review(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $roomType = RoomType::factory()->create();
        $booking  = $this->completedBooking($customer, $roomType);

        $review = Review::create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'user_id'      => $customer->id,
            'rating'       => 5,
            'status'       => 'visible',
        ]);

        $this->actingAsAdmin($admin)
            ->delete("/admin/reviews/{$review->id}")
            ->assertRedirect(route('admin.reviews.index'));

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_customer_cannot_access_admin_reviews(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->get('/admin/reviews')
            ->assertRedirect(route('customer.dashboard'));
    }
}
