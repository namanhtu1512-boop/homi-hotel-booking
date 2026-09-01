<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItemRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\BookingService;
use App\Services\EarlyCheckinRequestService;
use App\Services\GroupBookingRequestService;
use App\Services\LateCheckoutRequestService;
use App\Services\PromotionRequestService;
use App\Services\ReviewService;
use App\Services\RoomChangeRequestService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Dựng sẵn dữ liệu cho buổi demo bảo vệ đồ án — mỗi kịch bản tương ứng với 1
 * bước trong "docs/demo-script.md". KHÔNG gọi trực tiếp INSERT như
 * BookingSeeder cũ, mà đi qua đúng các service thật (BookingService,
 * RoomChangeRequestService...) để đảm bảo mọi state (payment, incidental
 * invoice, audit log...) đúng như luồng thật, không lệch với business logic
 * đang chạy trong app.
 *
 * Chạy riêng (KHÔNG nằm trong DatabaseSeeder mặc định):
 *   php artisan db:seed --class=DemoFlowSeeder
 *
 * Idempotent theo tiền tố "[DEMO-x]" trong cột note — chạy lại nhiều lần chỉ
 * tạo thêm những kịch bản chưa có, không nhân đôi dữ liệu cũ. Vì booking_code
 * do BookingService::create() tự sinh ngẫu nhiên, mã đơn của từng kịch bản sẽ
 * được in ra cuối cùng — chạy đúng ngay trước buổi demo để ngày tháng (hôm
 * nay/hôm qua/quá hạn...) luôn khớp thực tế, và ghi lại các mã in ra để dùng
 * khi demo.
 */
class DemoFlowSeeder extends Seeder
{
    private BookingService $bookings;
    private RoomChangeRequestService $roomChange;
    private EarlyCheckinRequestService $earlyCheckin;
    private LateCheckoutRequestService $lateCheckout;
    private GroupBookingRequestService $groupBooking;
    private PromotionRequestService $promotionRequest;
    private ReviewService $reviews;

    private User $customer;
    private User $customer2;
    private ?User $staff = null;
    private ?User $admin = null;

    /** @var array<int, array{scenario: string, code: ?string, note: string}> */
    private array $summary = [];

    public function run(): void
    {
        $this->bookings = app(BookingService::class);
        $this->roomChange = app(RoomChangeRequestService::class);
        $this->earlyCheckin = app(EarlyCheckinRequestService::class);
        $this->lateCheckout = app(LateCheckoutRequestService::class);
        $this->groupBooking = app(GroupBookingRequestService::class);
        $this->promotionRequest = app(PromotionRequestService::class);
        $this->reviews = app(ReviewService::class);

        $customer = User::where('email', 'customer@homi.test')->first();
        if (! $customer) {
            $this->command?->warn('Không tìm thấy customer@homi.test — hãy chạy UserSeeder trước (php artisan db:seed --class=UserSeeder).');

            return;
        }
        $this->customer = $customer;
        $this->customer2 = User::where('email', 'user@gmail.com')->first() ?? $customer;
        $this->staff = User::where('email', 'staff@homi.test')->first();
        $this->admin = User::where('email', 'admin@homi.test')->first();

        $this->scenarioA_PendingPayment();
        $this->scenarioB_ReadyForCheckin();
        $this->scenarioC_CurrentlyStaying();
        $this->scenarioD_OverdueCheckout();
        $this->scenarioE_CompletedWithReview();
        $this->scenarioF_CancelledWithRefund();
        $this->scenarioG_PendingRoomChange();
        $this->scenarioH_PendingEarlyCheckin();
        $this->scenarioI_PendingLateCheckout();
        $this->scenarioJ_GroupBookingInquiry();
        $this->scenarioK_PromotionProposal();

        Auth::logout();

        $this->printSummary();
    }

    /** Chạy 1 hành động nghiệp vụ dưới danh nghĩa 1 user cụ thể (audit log/note ghi đúng ai thao tác), rồi đăng xuất lại. */
    private function asUser(?User $user, \Closure $fn): mixed
    {
        if (! $user) {
            return $fn();
        }

        Auth::login($user);
        $result = $fn();
        Auth::logout();

        return $result;
    }

    private function alreadySeeded(string $tag): bool
    {
        return Booking::where('note', 'like', $tag . '%')->exists();
    }

    /** Trả hết các phòng đã nhận phòng nhưng chưa trả của 1 đơn (giống BookingController::checkOut() nhưng trả cả đơn 1 lượt). */
    private function checkOutAllRooms(Booking $booking): void
    {
        BookingItemRoom::query()
            ->whereHas('bookingItem', fn ($q) => $q->where('booking_id', $booking->id))
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->get()
            ->each(function (BookingItemRoom $bookingItemRoom) use (&$booking) {
                $result = $this->bookings->checkOutRoom($booking, $bookingItemRoom, ['method' => 'cash']);
                $booking = $result['booking'];
            });
    }

    private function roomTypeId(string $name): int
    {
        return RoomType::where('name', $name)->value('id');
    }

    private function firstRoomId(int $roomTypeId): int
    {
        $room = Room::where('room_type_id', $roomTypeId)
            ->orderBy('id')
            ->get()
            ->first(fn (Room $room) => ! $room->isOccupied());

        if (! $room) {
            throw new \RuntimeException("Không còn phòng trống cho loại phòng #{$roomTypeId} để dựng dữ liệu demo.");
        }

        return $room->id;
    }

    /**
     * BookingService::create() từ chối check_in ở quá khứ (DateRangeService)
     * — để dựng sẵn 1 đơn "đang lưu trú từ hôm qua" hay "đã hoàn tất 10 ngày
     * trước" cho demo, phải tạo đơn với ngày hợp lệ (hôm nay trở đi) rồi ghi
     * đè thẳng check_in/check_out về ngày mong muốn NGAY SAU KHI tạo, trước
     * khi gọi checkIn()/checkOut() — các hàm đó chỉ đọc trực tiếp 2 cột này
     * (isCheckInDateToday(), isOverdueCheckout()...), không gọi lại
     * DateRangeService nên không bị chặn.
     */
    private function backdate(Booking $booking, string $checkIn, string $checkOut): Booking
    {
        $booking->update(['check_in' => $checkIn, 'check_out' => $checkOut]);

        return $booking->fresh();
    }

    // ------------------------------------------------------------
    // A — Chờ đặt cọc/thanh toán (pending_deposit) — demo bước thanh
    // toán (VNPay sandbox / chuyển khoản / đặt cọc 30%) ngay từ đầu.
    // ------------------------------------------------------------
    private function scenarioA_PendingPayment(): void
    {
        $tag = '[DEMO-A]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $standard = $this->roomTypeId('Phòng Standard');

        $booking = $this->asUser($this->customer, fn () => $this->bookings->create($this->customer, [
            'check_in'  => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'items' => [[
                'room_type_id' => $standard,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0911000001',
            'note' => $tag . ' Sẵn sàng demo: đặt cọc 30%, chuyển khoản QR, hoặc VNPay sandbox.',
        ]));

        $this->summary[] = ['scenario' => 'A — Chờ thanh toán (demo đặt cọc/VNPay/chuyển khoản)', 'code' => $booking->booking_code, 'note' => 'Hết hạn giữ chỗ sau 15 phút kể từ lúc seed — seed lại ngay trước khi demo nếu đã seed từ lâu.'];
    }

    // ------------------------------------------------------------
    // B — Đã xác nhận + đã thanh toán, check-in HÔM NAY — demo bước
    // lễ tân/admin nhận phòng (gán phòng vật lý) trực tiếp trong buổi demo.
    // ------------------------------------------------------------
    private function scenarioB_ReadyForCheckin(): void
    {
        $tag = '[DEMO-B]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $superior = $this->roomTypeId('Phòng Superior');

        $booking = $this->asUser($this->customer, fn () => $this->bookings->create($this->customer, [
            'check_in'  => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'items' => [[
                'room_type_id' => $superior,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Lê Thị B',
            'customer_phone' => '0911000002',
            'note' => $tag . ' Sẵn sàng demo: nhận phòng (check-in) trực tiếp.',
        ]));

        $this->asUser($this->staff, fn () => $this->bookings->updatePaymentStatus($booking->fresh(), 'paid'));

        $this->summary[] = ['scenario' => 'B — Đã xác nhận, đã thanh toán (demo Check-in)', 'code' => $booking->booking_code, 'note' => 'Nếu demo trước 14:00 hệ thống có thể yêu cầu duyệt "nhận phòng sớm" trước — đây là tính năng thật, không phải lỗi (xem kịch bản H).'];
    }

    // ------------------------------------------------------------
    // C — Đang lưu trú (checked_in), nhận phòng từ hôm qua — demo thêm
    // dịch vụ / phụ phí / gia hạn ở giữa kỳ, rồi trả phòng trực tiếp.
    // ------------------------------------------------------------
    private function scenarioC_CurrentlyStaying(): void
    {
        $tag = '[DEMO-C]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $deluxe = $this->roomTypeId('Phòng Deluxe');

        $booking = $this->asUser($this->customer, fn () => $this->bookings->create($this->customer, [
            'check_in'  => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'items' => [[
                'room_type_id' => $deluxe,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Phạm Văn C',
            'customer_phone' => '0911000003',
            'note' => $tag . ' Sẵn sàng demo: thêm dịch vụ, thêm phụ phí, gia hạn, đổi phòng giữa kỳ, rồi trả phòng.',
        ]));

        $this->backdate($booking, now()->subDay()->toDateString(), now()->addDay()->toDateString());

        $this->asUser($this->staff, function () use ($booking) {
            $this->bookings->updatePaymentStatus($booking->fresh(), 'paid');

            $fresh = Booking::with('bookingItems')->find($booking->id);
            $item = $fresh->bookingItems->first();

            $this->bookings->checkIn($fresh, [$item->id => [$this->firstRoomId($item->room_type_id)]]);
        });

        $this->summary[] = ['scenario' => 'C — Đang lưu trú (demo dịch vụ/phụ phí/gia hạn/trả phòng)', 'code' => $booking->booking_code, 'note' => 'Trả phòng trực tiếp trong buổi demo sẽ chuyển thẳng sang "Hoàn tất" — có thể viết đánh giá ngay sau đó bằng tài khoản customer@homi.test.'];
    }

    // ------------------------------------------------------------
    // D — Đang lưu trú nhưng đã quá hạn trả phòng — demo cảnh báo
    // "Quá hạn trả phòng" trên dashboard admin/staff.
    // ------------------------------------------------------------
    private function scenarioD_OverdueCheckout(): void
    {
        $tag = '[DEMO-D]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $family = $this->roomTypeId('Phòng Family');

        $booking = $this->asUser($this->customer2, fn () => $this->bookings->create($this->customer2, [
            'check_in'  => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'items' => [[
                'room_type_id' => $family,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 2,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Hoàng Thị D',
            'customer_phone' => '0911000004',
            'note' => $tag . ' Sẵn sàng demo: dashboard cảnh báo "Quá hạn trả phòng".',
        ]));

        $this->backdate($booking, now()->subDays(3)->toDateString(), now()->subDay()->toDateString());

        $this->asUser($this->admin, function () use ($booking) {
            $this->bookings->updatePaymentStatus($booking->fresh(), 'paid');

            $fresh = Booking::with('bookingItems')->find($booking->id);
            $item = $fresh->bookingItems->first();

            $this->bookings->checkIn($fresh, [$item->id => [$this->firstRoomId($item->room_type_id)]]);
        });

        $this->summary[] = ['scenario' => 'D — Quá hạn trả phòng (demo cảnh báo dashboard)', 'code' => $booking->booking_code, 'note' => 'Cố tình để trạng thái "đang lưu trú" quá ngày trả phòng đã đặt — không cần thao tác gì thêm, chỉ mở dashboard admin/staff để xem cảnh báo.'];
    }

    // ------------------------------------------------------------
    // E — Đã hoàn tất, đã có đánh giá — demo hiển thị review trên trang
    // phòng + trang quản lý đánh giá của admin.
    // ------------------------------------------------------------
    private function scenarioE_CompletedWithReview(): void
    {
        $tag = '[DEMO-E]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $suite = $this->roomTypeId('Phòng Suite');

        $booking = $this->asUser($this->customer, fn () => $this->bookings->create($this->customer, [
            'check_in'  => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'items' => [[
                'room_type_id' => $suite,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0911000001',
            'note' => $tag . ' Đơn mẫu đã hoàn tất kèm đánh giá sẵn — demo /rooms và trang quản lý đánh giá.',
        ]));

        $this->backdate($booking, now()->subDays(10)->toDateString(), now()->subDays(8)->toDateString());

        $this->asUser($this->staff, function () use ($booking) {
            $this->bookings->updatePaymentStatus($booking->fresh(), 'paid');

            $fresh = Booking::with('bookingItems')->find($booking->id);
            $item = $fresh->bookingItems->first();
            $this->bookings->checkIn($fresh, [$item->id => [$this->firstRoomId($item->room_type_id)]]);

            $this->checkOutAllRooms(Booking::find($booking->id));
        });

        $this->reviews->create($this->customer, [
            'booking_id' => $booking->id,
            'rating'     => 5,
            'comment'    => 'Phòng Suite view đẹp, nhân viên hỗ trợ nhiệt tình, sẽ quay lại!',
        ]);

        $this->summary[] = ['scenario' => 'E — Đã hoàn tất + có đánh giá (demo review)', 'code' => $booking->booking_code, 'note' => 'Chỉ để xem, không cần thao tác gì thêm.'];
    }

    // ------------------------------------------------------------
    // F — Đã hủy sau khi thanh toán — demo tính phí hủy theo bậc +
    // hoàn tiền (kênh thủ công, tránh cần gọi VNPay sandbox thật).
    // ------------------------------------------------------------
    private function scenarioF_CancelledWithRefund(): void
    {
        $tag = '[DEMO-F]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $standard = $this->roomTypeId('Phòng Standard');

        $booking = $this->asUser($this->customer2, fn () => $this->bookings->create($this->customer2, [
            'check_in'  => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'items' => [[
                'room_type_id' => $standard,
                'quantity'     => 1,
                'adults'       => 1,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Hoàng Thị D',
            'customer_phone' => '0911000004',
            'note' => $tag . ' Đơn mẫu đã hủy sau khi thanh toán chuyển khoản — demo phí hủy theo bậc + hoàn tiền thủ công.',
        ]));

        $this->asUser($this->staff, fn () => $this->bookings->updatePaymentStatus($booking->fresh(), 'paid'));

        $this->bookings->cancelByCustomer($booking->id, $this->customer2);

        $this->summary[] = ['scenario' => 'F — Đã hủy + đã hoàn tiền (demo phí hủy/hoàn tiền)', 'code' => $booking->booking_code, 'note' => 'Check-in hôm nay nên phí hủy rơi vào bậc cao nhất (mất phần lớn/toàn bộ) — chỉ để xem trang chi tiết đơn, không cần thao tác gì thêm.'];
    }

    // ------------------------------------------------------------
    // G — Đã xác nhận, có yêu cầu đổi phòng đang chờ duyệt — demo
    // staff/admin duyệt hoặc từ chối yêu cầu đổi phòng.
    // ------------------------------------------------------------
    private function scenarioG_PendingRoomChange(): void
    {
        $tag = '[DEMO-G]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $standard = $this->roomTypeId('Phòng Standard');
        $deluxe   = $this->roomTypeId('Phòng Deluxe');

        $booking = $this->asUser($this->customer, fn () => $this->bookings->create($this->customer, [
            'check_in'  => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'items' => [[
                'room_type_id' => $standard,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0911000001',
            'note' => $tag . ' Sẵn sàng demo: duyệt/từ chối yêu cầu đổi phòng (Standard → Deluxe).',
        ]));

        $this->asUser($this->staff, fn () => $this->bookings->updatePaymentStatus($booking->fresh(), 'paid'));

        $this->roomChange->create($booking->fresh(), $this->customer, [
            'requested_room_type_id' => $deluxe,
            'reason' => 'Muốn phòng view đẹp hơn cho dịp kỷ niệm.',
        ]);

        $this->summary[] = ['scenario' => 'G — Yêu cầu đổi phòng chờ duyệt (demo duyệt/từ chối)', 'code' => $booking->booking_code, 'note' => 'Vào "Yêu cầu đổi phòng" (admin/staff) để duyệt hoặc từ chối trực tiếp.'];
    }

    // ------------------------------------------------------------
    // H — Đã xác nhận, check-in hôm nay, có yêu cầu nhận phòng sớm
    // đang chờ duyệt — demo duyệt xong rồi nhận phòng sớm luôn.
    // ------------------------------------------------------------
    private function scenarioH_PendingEarlyCheckin(): void
    {
        $tag = '[DEMO-H]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $superior = $this->roomTypeId('Phòng Superior');

        $booking = $this->asUser($this->customer2, fn () => $this->bookings->create($this->customer2, [
            'check_in'  => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'items' => [[
                'room_type_id' => $superior,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Hoàng Thị D',
            'customer_phone' => '0911000004',
            'note' => $tag . ' Sẵn sàng demo: duyệt yêu cầu nhận phòng sớm rồi check-in ngay.',
        ]));

        $this->asUser($this->staff, fn () => $this->bookings->updatePaymentStatus($booking->fresh(), 'paid'));

        $this->earlyCheckin->create($booking->fresh(), $this->customer2, [
            'requested_arrival_time' => '11:30',
            'reason' => 'Chuyến bay tới sớm, muốn nhận phòng ngay khi tới.',
        ]);

        $this->summary[] = ['scenario' => 'H — Yêu cầu nhận phòng sớm chờ duyệt (demo duyệt + check-in)', 'code' => $booking->booking_code, 'note' => 'Vào "Yêu cầu nhận phòng sớm" để duyệt (phí 300.000đ, 3 giờ sớm), rồi check-in đơn này để thấy phụ phí ghi vào hóa đơn phát sinh.'];
    }

    // ------------------------------------------------------------
    // I — Đang lưu trú, có yêu cầu trả phòng muộn đang chờ duyệt —
    // demo duyệt yêu cầu rồi trả phòng.
    // ------------------------------------------------------------
    private function scenarioI_PendingLateCheckout(): void
    {
        $tag = '[DEMO-I]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $deluxe = $this->roomTypeId('Phòng Deluxe');

        $booking = $this->asUser($this->customer, fn () => $this->bookings->create($this->customer, [
            'check_in'  => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'items' => [[
                'room_type_id' => $deluxe,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0911000001',
            'note' => $tag . ' Sẵn sàng demo: duyệt yêu cầu trả phòng muộn rồi trả phòng.',
        ]));

        $this->backdate($booking, now()->subDay()->toDateString(), now()->addDay()->toDateString());

        $this->asUser($this->staff, function () use ($booking) {
            $this->bookings->updatePaymentStatus($booking->fresh(), 'paid');

            $fresh = Booking::with('bookingItems')->find($booking->id);
            $item = $fresh->bookingItems->first();
            $this->bookings->checkIn($fresh, [$item->id => [$this->firstRoomId($item->room_type_id)]]);
        });

        $this->lateCheckout->create(Booking::find($booking->id), $this->customer, [
            'hours_late' => 3,
            'reason' => 'Chuyến bay tối muộn, muốn thư giãn thêm buổi chiều.',
        ]);

        $this->summary[] = ['scenario' => 'I — Yêu cầu trả phòng muộn chờ duyệt (demo duyệt + trả phòng)', 'code' => $booking->booking_code, 'note' => 'Vào "Yêu cầu trả phòng muộn" để duyệt (phí 450.000đ, 30% giá phòng — trễ 3 giờ), rồi trả phòng đơn này để thấy phụ phí ghi vào hóa đơn phát sinh.'];
    }

    // ------------------------------------------------------------
    // J — Yêu cầu tư vấn đặt phòng đoàn (public form) đang chờ xử lý.
    // ------------------------------------------------------------
    private function scenarioJ_GroupBookingInquiry(): void
    {
        $tag = '[DEMO-J]';
        if (\App\Models\GroupBookingRequest::where('company_name', 'Công ty Demo Du lịch Homi')->exists()) {
            return;
        }

        $request = $this->groupBooking->create([
            'company_name' => 'Công ty Demo Du lịch Homi',
            'contact_name' => 'Trần Thị Group',
            'email'        => 'group-demo@homi.test',
            'phone'        => '0911000099',
            'group_size'   => 12,
            'num_children' => 2,
            'num_infants'  => 0,
            'room_count'   => 6,
            'check_in'     => now()->addDays(15)->toDateString(),
            'check_out'    => now()->addDays(17)->toDateString(),
            'message'      => $tag . ' Đoàn công ty 12 người cần 6 phòng, có teambuilding — demo xử lý yêu cầu đặt đoàn.',
            'status'       => 'pending',
        ]);

        $this->summary[] = ['scenario' => 'J — Yêu cầu tư vấn đặt đoàn (demo báo đã liên hệ / tạo đơn / gửi báo giá)', 'code' => 'GroupBookingRequest #' . $request->id, 'note' => 'Vào "Yêu cầu đặt đoàn" (admin/staff) để xử lý.'];
    }

    // ------------------------------------------------------------
    // K — Nhân viên đề xuất mã khuyến mãi nhóm, đang chờ admin duyệt.
    // ------------------------------------------------------------
    private function scenarioK_PromotionProposal(): void
    {
        if (! $this->staff) {
            return;
        }

        $code = 'DEMOVIP10';
        if (\App\Models\Promotion::where('code', $code)->exists() || \App\Models\PromotionRequest::where('code', $code)->exists()) {
            return;
        }

        $request = $this->promotionRequest->propose($this->staff, [
            'code' => $code,
            'discount_percent' => 10,
            'reason' => 'Khách hàng thân thiết đặt phòng thường xuyên, đề xuất ưu đãi giữ chân khách.',
        ]);

        $this->summary[] = ['scenario' => 'K — Đề xuất mã khuyến mãi chờ duyệt (demo duyệt/từ chối)', 'code' => 'PromotionRequest #' . $request->id, 'note' => 'Vào "Đề xuất khuyến mãi" (admin) để duyệt hoặc từ chối mã ' . $code . '.'];
    }

    private function printSummary(): void
    {
        if (! $this->command) {
            return;
        }

        if ($this->summary === []) {
            $this->command->info('Tất cả kịch bản demo đã tồn tại từ trước (không tạo thêm gì mới).');

            return;
        }

        $this->command->info('Đã dựng xong dữ liệu demo:');
        $this->command->table(
            ['Kịch bản', 'Mã đơn / #', 'Ghi chú'],
            array_map(fn ($row) => [$row['scenario'], $row['code'], $row['note']], $this->summary)
        );
    }
}
