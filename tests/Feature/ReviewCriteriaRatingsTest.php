<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Review giờ có thêm 3 tiêu chí (vệ sinh/phục vụ/giá cả) bên cạnh số sao
 * tổng thể — cải thiện phần "khảo sát" thành khảo sát nhiều tiêu chí hơn
 * thay vì chỉ 1 số sao chung chung.
 */
class ReviewCriteriaRatingsTest extends TestCase
{
    use RefreshDatabase;

    private function completedBookingWithRoomType(User $customer): array
    {
        $roomType = RoomType::factory()->create();

        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'status'  => BookingStatus::COMPLETED,
        ]);

        BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
        ]);

        return [$booking, $roomType];
    }

    public function test_gui_danh_gia_thieu_tieu_chi_bi_tu_choi(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        [$booking, $roomType] = $this->completedBookingWithRoomType($customer);

        $response = $this->actingAs($customer)->post(route('customer.reviews.store'), [
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
            'rating'       => 5,
            // thiếu cleanliness_rating/service_rating/value_rating
        ]);

        $response->assertSessionHasErrors(['cleanliness_rating', 'service_rating', 'value_rating']);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_gui_danh_gia_du_tieu_chi_thanh_cong(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        [$booking, $roomType] = $this->completedBookingWithRoomType($customer);

        $response = $this->actingAs($customer)->post(route('customer.reviews.store'), [
            'booking_id'         => $booking->id,
            'room_type_id'       => $roomType->id,
            'rating'             => 5,
            'cleanliness_rating' => 4,
            'service_rating'     => 5,
            'value_rating'       => 3,
            'comment'            => 'Phòng sạch, nhân viên nhiệt tình.',
        ]);

        $response->assertRedirect(route('customer.bookings.index'));

        $review = Review::first();
        $this->assertNotNull($review);
        $this->assertSame(5, $review->rating);
        $this->assertSame(4, $review->cleanliness_rating);
        $this->assertSame(5, $review->service_rating);
        $this->assertSame(3, $review->value_rating);
    }
}
