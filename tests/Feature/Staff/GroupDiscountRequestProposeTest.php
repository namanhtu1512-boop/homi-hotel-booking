<?php

namespace Tests\Feature\Staff;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\GroupDiscountRequest;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\NewGroupDiscountRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GroupDiscountRequestProposeTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsStaff(): User
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        return $staff;
    }

    private function bookingWithPayment(int $total = 10000000): Booking
    {
        $booking = Booking::factory()->create([
            'status'          => BookingStatus::PENDING_DEPOSIT,
            'total_amount'    => $total,
            'discount_amount' => 0,
        ]);
        BookingItem::factory()->create(['booking_id' => $booking->id, 'quantity' => 12, 'nights' => 2]);
        Payment::create([
            'booking_id' => $booking->id,
            'method'     => PaymentMethod::PAY_AT_HOTEL,
            'amount'     => $total,
            'status'     => PaymentStatus::UNPAID,
        ]);

        return $booking;
    }

    public function test_de_xuat_trong_tran_duoc_ap_dung_ngay(): void
    {
        HotelInfo::instance()->update(['staff_max_group_discount_percent' => 5]);
        $this->loginAsStaff();
        $booking = $this->bookingWithPayment(10000000);

        $response = $this->post(route('staff.bookings.group-discount.store', $booking->id), [
            'percent' => 3,
            'reason'  => 'Khách quen',
        ]);

        $response->assertRedirect(route('staff.bookings.show', $booking->id));

        $booking->refresh();
        $this->assertSame(300000, $booking->discount_amount);
        $this->assertEquals(9700000, (float) $booking->total_amount);
        $this->assertEquals(9700000, (float) $booking->payment->amount);

        $request = GroupDiscountRequest::where('booking_id', $booking->id)->first();
        $this->assertSame('approved', $request->status);
        $this->assertNotNull($request->handled_by);
    }

    public function test_de_xuat_vuot_tran_tao_yeu_cau_cho_duyet_khong_dung_booking(): void
    {
        Notification::fake();
        HotelInfo::instance()->update(['staff_max_group_discount_percent' => 5]);
        $this->loginAsStaff();
        $booking = $this->bookingWithPayment(10000000);
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('staff.bookings.group-discount.store', $booking->id), [
            'percent' => 10,
            'reason'  => 'Đoàn ở 7 đêm',
        ]);

        $response->assertRedirect(route('staff.bookings.show', $booking->id));

        $booking->refresh();
        $this->assertSame(0, $booking->discount_amount);
        $this->assertEquals(10000000, (float) $booking->total_amount);
        $this->assertEquals(10000000, (float) $booking->payment->amount);

        $request = GroupDiscountRequest::where('booking_id', $booking->id)->first();
        $this->assertSame('pending', $request->status);

        Notification::assertSentTo($admin, NewGroupDiscountRequest::class);
    }

    public function test_khong_gui_duoc_2_de_xuat_dang_cho_cung_luc(): void
    {
        HotelInfo::instance()->update(['staff_max_group_discount_percent' => 5]);
        $this->loginAsStaff();
        $booking = $this->bookingWithPayment(10000000);

        $this->post(route('staff.bookings.group-discount.store', $booking->id), ['percent' => 10]);
        $this->assertSame(1, GroupDiscountRequest::where('booking_id', $booking->id)->count());

        $this->post(route('staff.bookings.group-discount.store', $booking->id), ['percent' => 8])
            ->assertSessionHas('error');
        $this->assertSame(1, GroupDiscountRequest::where('booking_id', $booking->id)->count());
    }

    public function test_khong_giam_vuot_qua_tong_tien_con_lai(): void
    {
        HotelInfo::instance()->update(['staff_max_group_discount_percent' => 100]);
        $this->loginAsStaff();
        // Đơn đã được giảm 500.000đ từ trước (VD chính sách tự động) — total_amount
        // còn 500.000, nhưng original_subtotal (total + discount) là 1.000.000.
        // Đề xuất 60% trên original_subtotal = 600.000đ, VƯỢT quá total_amount
        // hiện tại (500.000) — phải bị chặn, không được để total_amount âm.
        $booking = Booking::factory()->create([
            'status'          => BookingStatus::PENDING_DEPOSIT,
            'total_amount'    => 500000,
            'discount_amount' => 500000,
        ]);
        BookingItem::factory()->create(['booking_id' => $booking->id, 'quantity' => 12, 'nights' => 2]);
        Payment::create([
            'booking_id' => $booking->id,
            'method'     => PaymentMethod::PAY_AT_HOTEL,
            'amount'     => 500000,
            'status'     => PaymentStatus::UNPAID,
        ]);

        $this->post(route('staff.bookings.group-discount.store', $booking->id), ['percent' => 60])
            ->assertSessionHas('error');

        $booking->refresh();
        $this->assertSame(500000, $booking->discount_amount);
        $this->assertEquals(500000, (float) $booking->total_amount);
        $this->assertSame(0, GroupDiscountRequest::where('booking_id', $booking->id)->count());
    }
}
