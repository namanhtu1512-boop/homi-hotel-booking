<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\GroupDiscountRequest;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupDiscountRequestApproveRejectAdjustTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
    }

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

    private function pendingRequestFor(Booking $booking, float $percent = 10): GroupDiscountRequest
    {
        HotelInfo::instance()->update(['staff_max_group_discount_percent' => 5]);
        $this->loginAsStaff();
        $this->post(route('staff.bookings.group-discount.store', $booking->id), ['percent' => $percent, 'reason' => 'test']);

        return GroupDiscountRequest::where('booking_id', $booking->id)->latest()->first();
    }

    public function test_duyet_dung_muc_de_xuat(): void
    {
        $booking = $this->bookingWithPayment(10000000);
        $request = $this->pendingRequestFor($booking, 10);

        $this->loginAsAdmin();
        $this->post(route('admin.group-discount-requests.approve', $request->id))
            ->assertRedirect(route('admin.group-discount-requests.show', $request->id));

        $booking->refresh();
        $this->assertSame(1000000, $booking->discount_amount);
        $this->assertEquals(9000000, (float) $booking->total_amount);
        $this->assertEquals(9000000, (float) $booking->payment->amount);

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertEquals(10.0, (float) $request->approved_percent);
        $this->assertNotNull($request->handled_by);
    }

    public function test_tu_choi_khong_thay_doi_booking(): void
    {
        $booking = $this->bookingWithPayment(10000000);
        $request = $this->pendingRequestFor($booking, 10);

        $this->loginAsAdmin();
        $this->post(route('admin.group-discount-requests.reject', $request->id), ['admin_note' => 'Không phù hợp'])
            ->assertRedirect(route('admin.group-discount-requests.show', $request->id));

        $booking->refresh();
        $this->assertSame(0, $booking->discount_amount);
        $this->assertEquals(10000000, (float) $booking->total_amount);

        $this->assertSame('rejected', $request->fresh()->status);
    }

    public function test_dieu_chinh_ap_dung_muc_khac_khong_phai_muc_de_xuat(): void
    {
        $booking = $this->bookingWithPayment(10000000);
        $request = $this->pendingRequestFor($booking, 10);

        $this->loginAsAdmin();
        $this->post(route('admin.group-discount-requests.adjust', $request->id), ['percent' => 7])
            ->assertRedirect(route('admin.group-discount-requests.show', $request->id));

        $booking->refresh();
        $this->assertSame(700000, $booking->discount_amount);
        $this->assertEquals(9300000, (float) $booking->total_amount);

        $request->refresh();
        $this->assertEquals(10.0, (float) $request->requested_percent);
        $this->assertEquals(7.0, (float) $request->approved_percent);
    }

    public function test_khong_the_xu_ly_lai_yeu_cau_da_xu_ly(): void
    {
        $booking = $this->bookingWithPayment(10000000);
        $request = $this->pendingRequestFor($booking, 10);

        $this->loginAsAdmin();
        $this->post(route('admin.group-discount-requests.approve', $request->id));
        $booking->refresh();
        $discountAfterFirstApprove = $booking->discount_amount;

        $this->post(route('admin.group-discount-requests.approve', $request->id))
            ->assertSessionHas('error');

        $this->assertSame($discountAfterFirstApprove, $booking->fresh()->discount_amount);
    }

    public function test_nhan_vien_khong_duyet_duoc(): void
    {
        $booking = $this->bookingWithPayment(10000000);
        $request = $this->pendingRequestFor($booking, 10);

        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);
        session(['login_context' => 'staff']);

        $this->post(route('admin.group-discount-requests.approve', $request->id))
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_admin_ap_dung_truc_tiep_khong_bi_tran(): void
    {
        HotelInfo::instance()->update(['staff_max_group_discount_percent' => 5]);
        $booking = $this->bookingWithPayment(10000000);

        $this->loginAsAdmin();
        $this->post(route('admin.bookings.group-discount.store', $booking->id), ['percent' => 20])
            ->assertRedirect(route('admin.bookings.show', $booking->id));

        $booking->refresh();
        $this->assertSame(2000000, $booking->discount_amount);
        $this->assertEquals(8000000, (float) $booking->total_amount);
        $this->assertSame(0, GroupDiscountRequest::where('booking_id', $booking->id)->where('status', 'pending')->count());
    }
}
