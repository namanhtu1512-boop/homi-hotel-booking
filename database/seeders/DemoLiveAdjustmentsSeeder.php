<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItemRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Enums\BookingStatus;
use App\Services\AuditLogService;
use App\Services\BookingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Dọn dẹp + dựng thêm dữ liệu demo NGAY TRƯỚC 1 buổi demo cụ thể — chạy SAU
 * DemoFlowSeeder, bổ sung các kịch bản mà seeder đó chưa có (theo yêu cầu
 * trực tiếp của người dùng cho buổi demo lần này), và dọn lại vài thứ đã
 * "bẩn" do các buổi demo/test trước để lại (mã giảm giá đã dùng, phòng dirty
 * sau khi trả phòng).
 *
 * Chạy riêng (KHÔNG nằm trong DatabaseSeeder mặc định):
 *   php artisan db:seed --class=DemoLiveAdjustmentsSeeder
 *
 * Idempotent theo tiền tố "[DEMO-LIVE-x]" trong cột note, giống DemoFlowSeeder
 * — chạy lại nhiều lần chỉ tạo thêm phần chưa có. LƯU Ý: vì ngày check-in của
 * vài kịch bản tính tương đối theo "hôm nay" (VD kịch bản A dùng check-in =
 * hôm nay để rơi đúng bậc phí hủy 100%), chạy lại vào 1 NGÀY KHÁC sau khi đã
 * seed sẽ KHÔNG tự cập nhật lại ngày — muốn seed lại cho ngày demo mới phải
 * xóa đơn cũ theo tag rồi chạy lại (giống hạn chế đã có của DemoFlowSeeder).
 */
class DemoLiveAdjustmentsSeeder extends Seeder
{
    private BookingService $bookings;
    private AuditLogService $auditLog;

    private User $customer;
    private User $customer2;
    private User $staff;
    private User $staff2;
    private User $staff3;
    private User $admin;

    /** @var array<int, array{scenario: string, code: ?string, note: string}> */
    private array $summary = [];

    public function run(): void
    {
        $this->bookings = app(BookingService::class);
        $this->auditLog = app(AuditLogService::class);

        $customer = User::where('email', 'customer@homi.test')->first();
        if (! $customer) {
            $this->command?->warn('Không tìm thấy customer@homi.test — hãy chạy UserSeeder trước.');

            return;
        }
        $this->customer = $customer;
        $this->customer2 = User::where('email', 'user@gmail.com')->first() ?? $customer;
        $this->staff = User::where('email', 'staff@homi.test')->firstOrFail();
        $this->admin = User::where('email', 'admin@homi.test')->firstOrFail();

        $this->ensureStaffAccounts();
        $this->resetPromotionUsage();
        $this->cleanCheckedOutRooms();

        $this->scenarioA_PaidCloseToCheckinCancelFee();
        $this->scenarioB_CompletedNoReviewYet();
        $this->scenarioC_GroupMultiRoomTypeForRoomChange();
        $this->scenarioD_GroupChildrenExhaustExtraBeds();
        $this->scenarioE_DepositPaidAwaitingFinalConfirm();
        $this->scenarioF_ScatteredBookingsAcrossDatesAndStaff();

        // Kịch bản B tự trả phòng (checkOutAllRooms) SAU lượt dọn phòng đầu
        // tiên ở trên — dọn lại lần nữa để phòng vừa trả cũng sạch, đúng yêu
        // cầu "phòng đã checkout thì để sạch phòng" cho MỌI phòng đã checkout,
        // kể cả phòng vừa phát sinh trong chính lượt seed này.
        $this->cleanCheckedOutRooms();

        Auth::logout();

        $this->printSummary();
    }

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

    private function backdate(Booking $booking, string $checkIn, string $checkOut): Booking
    {
        $booking->update(['check_in' => $checkIn, 'check_out' => $checkOut]);

        return $booking->fresh();
    }

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

    // ------------------------------------------------------------
    // 0a — 2 tài khoản nhân viên bổ sung, để nhật ký hệ thống
    // (/admin/audit-logs) thấy nhiều nhân viên khác nhau thao tác.
    // ------------------------------------------------------------
    private function ensureStaffAccounts(): void
    {
        $this->staff2 = User::updateOrCreate(
            ['email' => 'staff2@homi.test'],
            [
                'name' => 'Staff Demo 2',
                'phone' => '0900000007',
                'address' => 'Hải Phòng',
                'role' => 'staff',
                'status' => 'active',
                'password' => bcrypt('123456'),
            ]
        );

        $this->staff3 = User::updateOrCreate(
            ['email' => 'staff3@homi.test'],
            [
                'name' => 'Staff Demo 3',
                'phone' => '0900000008',
                'address' => 'Nha Trang',
                'role' => 'staff',
                'status' => 'active',
                'password' => bcrypt('123456'),
            ]
        );

        $this->summary[] = ['scenario' => '0a — Tài khoản nhân viên bổ sung', 'code' => 'staff2@homi.test / staff3@homi.test', 'note' => 'Mật khẩu 123456 (giống các tài khoản demo khác).'];
    }

    // ------------------------------------------------------------
    // 0b — Hồi mã giảm giá đã dùng bởi các tài khoản khách demo, để
    // demo có thể nhập lại BẤT KỲ mã nào (EARLYBIRD20, GROUP15...).
    // ------------------------------------------------------------
    private function resetPromotionUsage(): void
    {
        $emails = [$this->customer->email, $this->customer2->email];

        $deleted = DB::table('booking_promotions')
            ->join('bookings', 'bookings.id', '=', 'booking_promotions.booking_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->whereIn('users.email', $emails)
            ->delete();

        $this->summary[] = ['scenario' => '0b — Hồi mã giảm giá', 'code' => "{$deleted} lượt dùng đã xóa", 'note' => 'Áp dụng cho ' . implode(', ', $emails) . ' — mọi mã (EARLYBIRD20, GROUP10/15/20...) đều nhập lại được.'];
    }

    // ------------------------------------------------------------
    // 0c — Dọn phòng "dirty" thuộc các đơn đã trả phòng/hoàn tất —
    // KHÔNG đụng tới đơn đang lưu trú quá hạn (vẫn đang ở, giữ nguyên
    // để demo cảnh báo quá hạn).
    // ------------------------------------------------------------
    private function cleanCheckedOutRooms(): void
    {
        $roomIds = Room::whereHas('bookingItemRooms', function ($q) {
            $q->whereNotNull('checked_out_at')
                ->whereHas('bookingItem.booking', function ($q2) {
                    $q2->whereIn('status', [BookingStatus::CHECKED_OUT->value, BookingStatus::COMPLETED->value]);
                });
        })->where('housekeeping_status', '!=', 'clean')->pluck('id');

        Room::whereIn('id', $roomIds)->update(['housekeeping_status' => 'clean']);

        $this->summary[] = ['scenario' => '0c — Dọn sạch phòng đã trả', 'code' => $roomIds->count() . ' phòng', 'note' => 'Chỉ áp dụng phòng thuộc đơn đã trả phòng/hoàn tất — đơn đang lưu trú quá hạn giữ nguyên.'];
    }

    // ------------------------------------------------------------
    // A — Đã thanh toán ĐỦ, check-in NGAY HÔM NAY — hủy trong buổi demo
    // sẽ rơi vào bậc phí hủy cao nhất (< 12 giờ tới giờ nhận phòng theo
    // Booking::cancellationFeePercent() → 100%, mất toàn bộ tiền đã trả
    // dù demo nói "hoàn tiền").
    // ------------------------------------------------------------
    private function scenarioA_PaidCloseToCheckinCancelFee(): void
    {
        $tag = '[DEMO-LIVE-A]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $deluxe = $this->roomTypeId('Phòng Deluxe');

        $booking = $this->asUser($this->customer, fn () => $this->bookings->create($this->customer, [
            'check_in'  => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString(),
            'items' => [[
                'room_type_id' => $deluxe,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0911000001',
            'note' => $tag . ' Đã thanh toán đủ, check-in hôm nay — demo hủy đơn để thấy mất phí (không phải hoàn tiền).',
        ]));

        $this->asUser($this->staff, fn () => $this->bookings->updatePaymentStatus($booking->fresh(), 'paid'));

        $this->summary[] = ['scenario' => 'A — Đã thanh toán, hủy sẽ mất phí (check-in hôm nay)', 'code' => $booking->booking_code, 'note' => 'KHÔNG tự hủy trước — vào "Đơn của tôi" (customer@homi.test) bấm Hủy trực tiếp trong buổi demo để thấy phí hủy 100%.'];
    }

    // ------------------------------------------------------------
    // B — Đã hoàn tất lưu trú nhưng CHƯA có đánh giá — demo viết đánh
    // giá tại /customer/reviews/create/{booking}.
    // ------------------------------------------------------------
    private function scenarioB_CompletedNoReviewYet(): void
    {
        $tag = '[DEMO-LIVE-B]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $superior = $this->roomTypeId('Phòng Superior');

        $booking = $this->asUser($this->customer, fn () => $this->bookings->create($this->customer, [
            'check_in'  => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString(),
            'items' => [[
                'room_type_id' => $superior,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0911000001',
            'note' => $tag . ' Đơn đã hoàn tất, CHƯA có đánh giá — demo viết đánh giá.',
        ]));

        $this->backdate($booking, now('Asia/Ho_Chi_Minh')->subDays(5)->toDateString(), now('Asia/Ho_Chi_Minh')->subDays(3)->toDateString());

        $this->asUser($this->staff, function () use ($booking) {
            $this->bookings->updatePaymentStatus($booking->fresh(), 'paid');

            $fresh = Booking::with('bookingItems')->find($booking->id);
            $item = $fresh->bookingItems->first();
            $this->bookings->checkIn($fresh, [$item->id => [$this->firstRoomId($item->room_type_id)]]);

            $this->checkOutAllRooms(Booking::find($booking->id));
        });

        $this->summary[] = ['scenario' => 'B — Đã hoàn tất, chưa đánh giá (demo viết đánh giá)', 'code' => $booking->booking_code, 'note' => 'Đăng nhập customer@homi.test → /customer/reviews/create/' . $booking->id];
    }

    // ------------------------------------------------------------
    // C — Đơn đoàn 2 loại phòng khác nhau, đã thanh toán, CHƯA check-in
    // — demo đổi phòng từ "Đơn của tôi" (RoomChangeRequestService chỉ
    // cho đổi khi đơn còn pending/confirmed, chưa check-in).
    // ------------------------------------------------------------
    private function scenarioC_GroupMultiRoomTypeForRoomChange(): void
    {
        $tag = '[DEMO-LIVE-C]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $standard = $this->roomTypeId('Phòng Standard');
        $deluxe   = $this->roomTypeId('Phòng Deluxe');

        $booking = $this->asUser($this->customer, fn () => $this->bookings->create($this->customer, [
            'check_in'  => now('Asia/Ho_Chi_Minh')->addDays(5)->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDays(7)->toDateString(),
            'items' => [
                ['room_type_id' => $standard, 'quantity' => 1, 'adults' => 2, 'children' => 0, 'infants' => 0],
                ['room_type_id' => $deluxe,   'quantity' => 1, 'adults' => 2, 'children' => 0, 'infants' => 0],
            ],
            'customer_name'  => 'Nguyễn Văn A',
            'customer_phone' => '0911000001',
            'note' => $tag . ' Đơn đoàn 2 loại phòng (Standard + Deluxe), đã thanh toán — demo đổi phòng ngay trong "Đơn của tôi".',
        ]));

        $this->asUser($this->staff, fn () => $this->bookings->updatePaymentStatus($booking->fresh(), 'paid'));

        $this->summary[] = ['scenario' => 'C — Đoàn 2 loại phòng, đã thanh toán (demo đổi phòng)', 'code' => $booking->booking_code, 'note' => 'Đăng nhập customer@homi.test → "Đơn của tôi" → chọn đơn → Đổi phòng (chọn đúng 1 dòng Standard hoặc Deluxe).'];
    }

    // ------------------------------------------------------------
    // D — Đơn đoàn 12 trẻ em, 11-12/09/2026 — tổng giường phụ CẦN đúng
    // bằng toàn bộ pool hiện tại (hotel_info.extra_beds_total = 12) nên
    // sau đơn này countAvailable() cho 2 ngày đó = 0 → đơn kế tiếp cần
    // giường phụ trong khoảng ngày này sẽ rơi vào "chờ tư vấn".
    // ------------------------------------------------------------
    private function scenarioD_GroupChildrenExhaustExtraBeds(): void
    {
        $tag = '[DEMO-LIVE-D]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $standard = $this->roomTypeId('Phòng Standard');
        $superior = $this->roomTypeId('Phòng Superior');

        $booking = $this->asUser($this->staff, fn () => $this->bookings->createByAdmin([
            'check_in'  => '2026-09-11',
            'check_out' => '2026-09-12',
            'items' => [
                ['room_type_id' => $standard, 'quantity' => 6, 'adults' => 12, 'children' => 6, 'infants' => 0, 'extra_bed' => true],
                ['room_type_id' => $superior, 'quantity' => 6, 'adults' => 12, 'children' => 6, 'infants' => 0, 'extra_bed' => true],
            ],
            'customer_name'  => 'Đoàn Trường Tiểu học Hoa Mai',
            'customer_phone' => '0911000099',
            'customer_email' => 'doan-hoamai@homi.test',
            'note' => $tag . ' Đoàn 12 trẻ em (11-12/09) — chiếm hết pool giường phụ hiện có, demo tính năng "hết giường phụ" cho đơn kế tiếp.',
        ]));

        $this->asUser($this->staff, fn () => $this->bookings->updatePaymentStatus($booking->fresh(), 'paid'));

        $this->summary[] = ['scenario' => 'D — Đoàn 12 trẻ em, hết giường phụ (11-12/09/2026)', 'code' => $booking->booking_code, 'note' => 'Sau đơn này, pool giường phụ 11-12/09 = 0 — thử tạo thêm 1 đơn khác cần giường phụ cùng ngày để thấy rơi vào "chờ tư vấn".'];
    }

    // ------------------------------------------------------------
    // E — Đã đặt cọc 30% (mô phỏng), CHƯA thanh toán đủ — demo admin
    // vào xác nhận đã thu nốt phần còn lại.
    // ------------------------------------------------------------
    private function scenarioE_DepositPaidAwaitingFinalConfirm(): void
    {
        $tag = '[DEMO-LIVE-E]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $family = $this->roomTypeId('Phòng Family');

        $booking = $this->asUser($this->customer2, fn () => $this->bookings->create($this->customer2, [
            'check_in'  => now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDays(5)->toDateString(),
            'items' => [[
                'room_type_id' => $family,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 1,
                'infants'      => 0,
            ]],
            'customer_name'  => 'Hoàng Thị D',
            'customer_phone' => '0911000004',
            'note' => $tag . ' Đã đặt cọc 30% — demo admin xác nhận thu nốt phần còn lại.',
        ]));

        $this->asUser($this->customer2, fn () => $this->bookings->payDepositDemo($booking->id, $this->customer2));

        $this->summary[] = ['scenario' => 'E — Đã đặt cọc 30% (demo admin xác nhận thu nốt)', 'code' => $booking->booking_code, 'note' => 'Vào chi tiết đơn (admin/staff) → "Xác nhận đã thu đủ số tiền còn lại".'];
    }

    // ------------------------------------------------------------
    // F — Vài đơn nhỏ dàn trải nhiều ngày/loại phòng khác nhau, xử lý
    // bởi nhiều nhân viên khác nhau (staff/staff2/staff3/admin) — demo
    // lọc ngày + loại phòng thấy chênh lệch phòng trống, và nhật ký hệ
    // thống (/admin/audit-logs) thấy nhiều người thao tác.
    // ------------------------------------------------------------
    private function scenarioF_ScatteredBookingsAcrossDatesAndStaff(): void
    {
        $tag = '[DEMO-LIVE-F]';
        if ($this->alreadySeeded($tag)) {
            return;
        }

        $standard = $this->roomTypeId('Phòng Standard');
        $superior = $this->roomTypeId('Phòng Superior');
        $deluxe   = $this->roomTypeId('Phòng Deluxe');
        $family   = $this->roomTypeId('Phòng Family');
        $suite    = $this->roomTypeId('Phòng Suite');

        $rows = [
            ['n' => 1, 'type' => $standard, 'qty' => 1, 'adults' => 2, 'children' => 0, 'offset' => 2, 'nights' => 2, 'customer' => $this->customer,  'actor' => $this->staff2, 'checkin' => true],
            ['n' => 2, 'type' => $superior, 'qty' => 2, 'adults' => 4, 'children' => 0, 'offset' => 2, 'nights' => 2, 'customer' => $this->customer2, 'actor' => $this->staff3, 'checkin' => false],
            ['n' => 3, 'type' => $deluxe,   'qty' => 1, 'adults' => 2, 'children' => 0, 'offset' => 6, 'nights' => 2, 'customer' => $this->customer,  'actor' => $this->staff,  'checkin' => false],
            ['n' => 4, 'type' => $family,   'qty' => 2, 'adults' => 4, 'children' => 2, 'offset' => 9, 'nights' => 3, 'customer' => $this->customer2, 'actor' => $this->staff2, 'checkin' => false],
            ['n' => 5, 'type' => $suite,    'qty' => 1, 'adults' => 2, 'children' => 0, 'offset' => 13, 'nights' => 3, 'customer' => $this->customer, 'actor' => $this->staff3, 'checkin' => false],
            ['n' => 6, 'type' => $standard, 'qty' => 3, 'adults' => 6, 'children' => 0, 'offset' => 20, 'nights' => 3, 'customer' => $this->customer2, 'actor' => $this->staff,  'checkin' => false, 'cancel_by_admin' => true],
        ];

        foreach ($rows as $row) {
            $lineTag = '[DEMO-LIVE-F' . $row['n'] . ']';

            $checkIn = now('Asia/Ho_Chi_Minh')->addDays($row['offset'])->toDateString();
            $checkOut = now('Asia/Ho_Chi_Minh')->addDays($row['offset'] + $row['nights'])->toDateString();

            $booking = $this->asUser($row['customer'], fn () => $this->bookings->create($row['customer'], [
                'check_in'  => $checkIn,
                'check_out' => $checkOut,
                'items' => [[
                    'room_type_id' => $row['type'],
                    'quantity'     => $row['qty'],
                    'adults'       => $row['adults'],
                    'children'     => $row['children'],
                    'infants'      => 0,
                ]],
                'customer_name'  => $row['customer']->name,
                'customer_phone' => $row['customer']->phone ?? '0911000000',
                'note' => $lineTag . " Đơn rải rác #{$row['n']} — {$checkIn} → {$checkOut}, demo lọc ngày/loại phòng.",
            ]));

            $this->asUser($row['actor'], function () use ($booking, $row) {
                $fresh = $booking->fresh();
                $this->bookings->updatePaymentStatus($fresh, 'paid');
                $this->auditLog->log('booking.payment_updated', $fresh, "Cập nhật thanh toán đơn \"{$fresh->booking_code}\" thành \"paid\".");

                if ($row['checkin']) {
                    $fresh2 = Booking::with('bookingItems')->find($booking->id);
                    $item = $fresh2->bookingItems->first();
                    $this->bookings->checkIn($fresh2, [$item->id => [$this->firstRoomId($item->room_type_id)]]);
                    $this->auditLog->log('booking.checked_in', $fresh2, "Check-in đơn \"{$fresh2->booking_code}\".");
                }
            });

            if (! empty($row['cancel_by_admin'])) {
                $this->asUser($this->admin, function () use ($booking) {
                    $fresh = Booking::find($booking->id);
                    $this->bookings->cancelByAdmin($fresh);
                    $this->auditLog->log('booking.cancelled', $fresh, "Hủy đơn \"{$fresh->booking_code}\".");
                });
            }

            $this->summary[] = ['scenario' => "F{$row['n']} — Đơn rải rác ({$row['actor']->email})", 'code' => $booking->booking_code, 'note' => "{$checkIn} → {$checkOut}" . (! empty($row['cancel_by_admin']) ? ' (đã bị admin hủy sau đó — demo phòng được nhả lại)' : '')];
        }
    }

    private function printSummary(): void
    {
        if (! $this->command) {
            return;
        }

        if ($this->summary === []) {
            $this->command->info('Tất cả kịch bản demo (đợt live này) đã tồn tại từ trước (không tạo thêm gì mới).');

            return;
        }

        $this->command->info('Đã dựng xong dữ liệu demo (đợt live):');
        $this->command->table(
            ['Kịch bản', 'Mã đơn / #', 'Ghi chú'],
            array_map(fn ($row) => [$row['scenario'], $row['code'], $row['note']], $this->summary)
        );
    }
}
