<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCancelRefundAmountTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        session(['login_context' => 'admin']);

        return $admin;
    }

    private function confirmedBooking(): Booking
    {
        $roomType = RoomType::factory()->create();

        $booking = Booking::factory()->create([
            'status'       => BookingStatus::CONFIRMED,
            'total_amount' => 2_000_000,
        ]);

        BookingItem::factory()->create([
            'booking_id'   => $booking->id,
            'room_type_id' => $roomType->id,
        ]);

        return $booking;
    }

    public function test_huy_don_da_thanh_toan_thu_cong_thi_cho_admin_bam_xac_nhan_da_hoan_tien(): void
    {
        $this->loginAsAdmin();
        $booking = $this->confirmedBooking();

        Payment::create([
            'booking_id'        => $booking->id,
            'method'            => PaymentMethod::PAY_AT_HOTEL,
            'amount'            => 2_000_000,
            'amount_collected'  => 2_000_000,
            'status'            => PaymentStatus::PAID,
            'paid_at'           => now(),
        ]);

        $response = $this->post(route('admin.bookings.cancel', $booking->id));

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $response->assertSessionHas('error', "Đã hủy đơn {$booking->booking_code} — cần xử lý hoàn tiền thủ công 2.000.000đ cho khách (bấm \"Xác nhận đã hoàn tiền\" sau khi đã trả tiền thật cho khách).");

        // Chưa bấm xác nhận — payment vẫn PAID, GIỮ NGUYÊN amount_collected
        // (đã trừ phí hủy) để nút "Xác nhận đã hoàn tiền cho khách" tự hiện
        // đúng số trên trang chi tiết đơn (xem _payment-confirm-modal.blade.php).
        $this->assertDatabaseHas('payments', [
            'booking_id'       => $booking->id,
            'status'           => PaymentStatus::PAID->value,
            'amount_collected' => 2_000_000,
        ]);

        $show = $this->get(route('admin.bookings.show', $booking->id));
        $show->assertOk();
        $show->assertSee('Xác nhận đã hoàn tiền cho khách');

        // Admin bấm nút xác nhận sau khi đã trả tiền thật cho khách ngoài đời.
        $confirm = $this->patch(route('admin.bookings.update-payment', $booking->id), ['status' => 'refunded']);
        $confirm->assertRedirect(route('admin.bookings.show', $booking->id));

        $this->assertDatabaseHas('payments', [
            'booking_id'       => $booking->id,
            'status'           => PaymentStatus::REFUNDED->value,
            'amount_collected' => 0,
        ]);
    }

    public function test_huy_don_moi_dat_coc_thi_khong_hoan_gi_va_khong_bao_hoan_tien(): void
    {
        $this->loginAsAdmin();
        $booking = $this->confirmedBooking();

        Payment::create([
            'booking_id'       => $booking->id,
            'method'           => PaymentMethod::CASH_WITH_DEPOSIT,
            'amount'           => 2_000_000,
            'amount_collected' => 0,
            'deposit_amount'   => 600_000,
            'status'           => PaymentStatus::DEPOSIT_PAID,
            'deposit_paid_at'  => now(),
        ]);

        $response = $this->post(route('admin.bookings.cancel', $booking->id));

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $response->assertSessionHas('success', "Đã hủy đơn {$booking->booking_code}.");
    }

    public function test_huy_don_vnpay_thieu_thong_tin_giao_dich_bao_can_hoan_thu_cong_kem_so_tien(): void
    {
        $this->loginAsAdmin();
        $booking = $this->confirmedBooking();

        Payment::create([
            'booking_id'             => $booking->id,
            'method'                 => PaymentMethod::ONLINE_VNPAY,
            'amount'                 => 2_000_000,
            'amount_collected'       => 2_000_000,
            'status'                 => PaymentStatus::PAID,
            'paid_at'                => now(),
            'gateway_transaction_no' => null,
        ]);

        $response = $this->post(route('admin.bookings.cancel', $booking->id));

        $response->assertRedirect(route('admin.bookings.show', $booking->id));
        $response->assertSessionHas('error', "Đã hủy đơn {$booking->booking_code} — cần xử lý hoàn tiền thủ công 2.000.000đ cho khách (bấm \"Xác nhận đã hoàn tiền\" sau khi đã trả tiền thật cho khách).");
    }

    public function test_trang_chi_tiet_hien_canh_bao_so_tien_hoan_truoc_khi_huy(): void
    {
        $this->loginAsAdmin();
        $booking = $this->confirmedBooking();

        Payment::create([
            'booking_id'       => $booking->id,
            'method'           => PaymentMethod::PAY_AT_HOTEL,
            'amount'           => 2_000_000,
            'amount_collected' => 2_000_000,
            'status'           => PaymentStatus::PAID,
            'paid_at'          => now(),
        ]);

        $response = $this->get(route('admin.bookings.show', $booking->id));

        $response->assertOk();
        $response->assertSee('Nếu hủy đơn này, sẽ hoàn 2.000.000đ cho khách.');
    }

    public function test_trang_chi_tiet_hien_canh_bao_khong_hoan_coc_truoc_khi_huy(): void
    {
        $this->loginAsAdmin();
        $booking = $this->confirmedBooking();

        Payment::create([
            'booking_id'       => $booking->id,
            'method'           => PaymentMethod::CASH_WITH_DEPOSIT,
            'amount'           => 2_000_000,
            'amount_collected' => 0,
            'deposit_amount'   => 600_000,
            'status'           => PaymentStatus::DEPOSIT_PAID,
            'deposit_paid_at'  => now(),
        ]);

        $response = $this->get(route('admin.bookings.show', $booking->id));

        $response->assertOk();
        $response->assertSee('tiền cọc đã đặt sẽ KHÔNG được hoàn', false);
    }
}
