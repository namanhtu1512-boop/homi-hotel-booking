<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\GroupDiscountPolicy;
use App\Models\GroupDiscountRequest;
use App\Models\HotelInfo;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\GroupDiscountRequestResolved;
use App\Notifications\NewGroupDiscountRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ưu đãi khách đoàn — 2 nguồn giảm giá trên cùng 1 đơn:
 *   - 'policy_tier': tự động theo bậc số phòng (GroupDiscountPolicyService),
 *     áp ngay lúc tạo đơn, không cần duyệt (đã được admin duyệt sẵn qua
 *     chính sách) — xem recordAutoTierApplied(), gọi từ BookingService::createByAdmin().
 *   - 'staff_extra': nhân viên/admin đề xuất giảm THÊM ngoài chính sách.
 *     Admin luôn áp ngay; nhân viên chỉ áp ngay nếu % <= trần cấu hình
 *     (hotel_info.staff_max_group_discount_percent), ngược lại tạo yêu cầu
 *     'pending' chờ admin duyệt/từ chối/điều chỉnh (xem proposeExtra()/approve()/reject()).
 *
 * % luôn tính trên original_subtotal = total_amount + discount_amount CỦA
 * BOOKING TẠI THỜI ĐIỂM đề xuất (không phải giá gốc lúc tạo đơn) — mỗi lượt
 * giảm thêm tính trên phần còn lại hiện tại, không cộng dồn sai khi giảm
 * nhiều lần.
 */
class GroupDiscountRequestService
{
    public function hasPendingRequest(Booking $booking): bool
    {
        return GroupDiscountRequest::where('booking_id', $booking->id)
            ->where('type', 'staff_extra')
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Ghi nhận bậc giảm giá TỰ ĐỘNG áp dụng lúc tạo đơn — gọi TRONG transaction
     * của BookingService::createByAdmin(). status='approved' ngay, handled_by
     * = null nghĩa là hệ thống tự áp theo chính sách, không phải quyết định
     * thủ công của admin.
     */
    public function recordAutoTierApplied(Booking $booking, GroupDiscountPolicy $policy, int $roomCount, int $originalSubtotal, int $discountAmount, ?int $actorId): GroupDiscountRequest
    {
        return GroupDiscountRequest::create([
            'booking_id'                 => $booking->id,
            'user_id'                    => $actorId,
            'group_discount_policy_id'   => $policy->id,
            'type'                       => 'policy_tier',
            'room_count'                 => $roomCount,
            'original_subtotal'          => $originalSubtotal,
            'requested_percent'          => $policy->discount_percent,
            'requested_amount'           => $discountAmount,
            'approved_percent'           => $policy->discount_percent,
            'approved_amount'            => $discountAmount,
            'status'                     => 'approved',
            'handled_by'                 => null,
            'handled_at'                 => now(),
        ]);
    }

    /**
     * Nhân viên/admin đề xuất giảm thêm $percent% (tính trên original_subtotal
     * hiện tại của booking).
     *   - Admin: luôn áp dụng ngay, không có trần.
     *   - Nhân viên: áp dụng ngay nếu $percent <= trần cấu hình, ngược lại
     *     tạo yêu cầu 'pending', KHÔNG đụng vào booking.
     *
     * @return array{applied: bool, request: GroupDiscountRequest, booking: Booking}
     */
    public function proposeExtra(Booking $booking, User $actor, float $percent, ?string $reason): array
    {
        if (! $booking->canApplyGroupDiscount()) {
            throw ValidationException::withMessages([
                'status' => ['Đơn ở trạng thái này không thể áp dụng giảm giá (đã hủy/trả phòng/hoàn thành hoặc đã thanh toán đủ).'],
            ]);
        }

        if ($this->hasPendingRequest($booking)) {
            throw ValidationException::withMessages([
                'status' => ['Đơn này đang có 1 đề xuất giảm giá chờ duyệt, vui lòng chờ xử lý xong trước khi gửi đề xuất mới.'],
            ]);
        }

        $cap = (float) HotelInfo::instance()->staff_max_group_discount_percent;
        $withinCap = $actor->isAdmin() || $percent <= $cap;

        $originalSubtotal = (int) round((float) $booking->total_amount + (float) $booking->discount_amount);
        $amount = (int) round($originalSubtotal * $percent / 100);
        $roomCount = (int) $booking->bookingItems->sum('quantity');

        if ($amount > (float) $booking->total_amount) {
            throw ValidationException::withMessages([
                'percent' => ['Mức giảm vượt quá tổng tiền còn lại của đơn.'],
            ]);
        }

        if (! $withinCap) {
            $request = GroupDiscountRequest::create([
                'booking_id'                  => $booking->id,
                'user_id'                     => $actor->id,
                'group_discount_policy_id'    => null,
                'type'                        => 'staff_extra',
                'room_count'                  => $roomCount,
                'original_subtotal'           => $originalSubtotal,
                'requested_percent'           => $percent,
                'requested_amount'            => $amount,
                'reason'                      => $reason,
                'status'                      => 'pending',
                'staff_cap_percent_snapshot'  => $cap,
            ]);

            User::where('role', 'admin')->each(fn (User $u) => $u->notify(new NewGroupDiscountRequest($request)));

            return ['applied' => false, 'request' => $request, 'booking' => $booking];
        }

        return DB::transaction(function () use ($booking, $actor, $percent, $amount, $reason, $originalSubtotal, $roomCount, $cap) {
            $locked  = Booking::whereKey($booking->id)->lockForUpdate()->first();
            $payment = Payment::where('booking_id', $locked->id)->lockForUpdate()->first();

            $locked->update([
                'discount_amount' => $locked->discount_amount + $amount,
                'total_amount'    => $locked->total_amount - $amount,
            ]);
            $payment?->update(['amount' => $payment->amount - $amount]);

            $request = GroupDiscountRequest::create([
                'booking_id'                  => $locked->id,
                'user_id'                     => $actor->id,
                'group_discount_policy_id'    => null,
                'type'                        => 'staff_extra',
                'room_count'                  => $roomCount,
                'original_subtotal'           => $originalSubtotal,
                'requested_percent'           => $percent,
                'requested_amount'            => $amount,
                'approved_percent'            => $percent,
                'approved_amount'             => $amount,
                'reason'                      => $reason,
                'status'                      => 'approved',
                'staff_cap_percent_snapshot'  => $cap,
                'handled_by'                  => $actor->id,
                'handled_at'                  => now(),
            ]);

            return ['applied' => true, 'request' => $request, 'booking' => $locked->fresh(['payment'])];
        });
    }

    /**
     * Admin duyệt — $overridePercent null nghĩa là duyệt ĐÚNG % đã đề xuất;
     * truyền 1 giá trị khác nghĩa là "điều chỉnh" (vẫn cùng 1 hàm, chỉ khác
     * % nào được dùng để tính tiền — status vẫn chỉ là 'approved', bản thân
     * approved_percent != requested_percent chính là dấu vết đã điều chỉnh).
     *
     * @return array{request: GroupDiscountRequest, booking: Booking}
     */
    public function approve(GroupDiscountRequest $request, User $admin, ?float $overridePercent, ?string $adminNote): array
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Yêu cầu này đã được xử lý trước đó.'],
            ]);
        }

        $percent = $overridePercent ?? (float) $request->requested_percent;
        $amount  = (int) round((float) $request->original_subtotal * $percent / 100);

        return DB::transaction(function () use ($request, $admin, $percent, $amount, $adminNote) {
            $booking = Booking::whereKey($request->booking_id)->lockForUpdate()->first();
            $payment = Payment::where('booking_id', $booking->id)->lockForUpdate()->first();

            if ($amount > (float) $booking->total_amount) {
                throw ValidationException::withMessages([
                    'percent' => ['Mức giảm vượt quá tổng tiền còn lại của đơn — không thể duyệt.'],
                ]);
            }

            $booking->update([
                'discount_amount' => $booking->discount_amount + $amount,
                'total_amount'    => $booking->total_amount - $amount,
            ]);
            $payment?->update(['amount' => $payment->amount - $amount]);

            $request->update([
                'status'           => 'approved',
                'approved_percent' => $percent,
                'approved_amount'  => $amount,
                'admin_note'       => $adminNote,
                'handled_by'       => $admin->id,
                'handled_at'       => now(),
            ]);

            $wasAdjusted = $percent != (float) $request->requested_percent;
            $request->fresh()->user?->notify(new GroupDiscountRequestResolved($request->fresh(), 'approved', $wasAdjusted));

            return ['request' => $request->fresh(), 'booking' => $booking->fresh(['payment'])];
        });
    }

    public function reject(GroupDiscountRequest $request, User $admin, ?string $note): GroupDiscountRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Yêu cầu này đã được xử lý trước đó.'],
            ]);
        }

        $request->update([
            'status'     => 'rejected',
            'admin_note' => $note,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);

        $request->fresh()->user?->notify(new GroupDiscountRequestResolved($request->fresh(), 'rejected', false));

        return $request->fresh();
    }

    public function adminList(array $filters = []): LengthAwarePaginator
    {
        $query = GroupDiscountRequest::with(['booking', 'user', 'handledByUser'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function myList(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = GroupDiscountRequest::with('booking')->where('user_id', $userId)->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15)->withQueryString();
    }
}
