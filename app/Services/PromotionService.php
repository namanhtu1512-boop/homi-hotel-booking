<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    public function list(): Collection
    {
        return Promotion::withTrashed()->latest()->get();
    }

    /**
     * Danh sách cho nhân viên xem tham khảo (VD báo mã cho khách) — không có
     * quyền tạo/sửa/xóa nên không cần thấy các mã đã xóa như admin.
     */
    public function listVisible(): Collection
    {
        return Promotion::latest()->get();
    }

    public function activePublic(): Collection
    {
        return $this->activeNowQuery()->orderBy('ends_at')->get();
    }

    /**
     * Mã khuyến mãi đoàn/nhóm đang hiệu lực — dùng cho form Đặt đoàn/nhóm
     * (chỉ hiển thị tham khảo, tái dùng đúng điều kiện "đang hiệu lực" của
     * activePublic() thay vì viết lại logic ngày starts_at/ends_at).
     */
    public function activeGroupPromotions(): Collection
    {
        return $this->activeNowQuery()->where('is_group_promo', true)->orderBy('code')->get();
    }

    private function activeNowQuery()
    {
        $today = today()->toDateString();

        return Promotion::active()
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today));
    }

    public function find(int $id): Promotion
    {
        return Promotion::withTrashed()->findOrFail($id);
    }

    /**
     * Tìm khuyến mãi hợp lệ theo mã — dùng khi khách nhập mã ở bước đặt phòng.
     * Nếu truyền $user, mã đã được chính tài khoản đó dùng ở một đơn chưa
     * hủy trước đó sẽ bị từ chối — mỗi tài khoản chỉ được dùng 1 lần/mã.
     *
     * @throws ValidationException
     */
    public function findValidByCode(string $code, ?User $user = null): Promotion
    {
        $promotion = Promotion::where('code', $code)->first();

        if (! $promotion || ! $promotion->isValidNow()) {
            throw ValidationException::withMessages([
                'promo_codes' => ["Mã giảm giá \"{$code}\" không hợp lệ hoặc đã hết hạn."],
            ]);
        }

        if ($user && $this->alreadyUsedByUser($promotion, $user)) {
            throw ValidationException::withMessages([
                'promo_codes' => ["Bạn đã sử dụng mã \"{$code}\" rồi — mỗi tài khoản chỉ được dùng 1 lần cho mỗi mã."],
            ]);
        }

        return $promotion;
    }

    /**
     * Tài khoản đã dùng mã này ở một đơn chưa hủy (booking_promotions nối
     * bookings) chưa — đơn đã hủy không tính là "đã dùng" để khách có thể
     * dùng lại mã sau khi hủy đơn.
     */
    private function alreadyUsedByUser(Promotion $promotion, User $user): bool
    {
        return DB::table('booking_promotions')
            ->join('bookings', 'bookings.id', '=', 'booking_promotions.booking_id')
            ->where('booking_promotions.promotion_id', $promotion->id)
            ->where('bookings.user_id', $user->id)
            ->where('bookings.status', '!=', BookingStatus::CANCELLED->value)
            ->exists();
    }

    /**
     * Tìm nhiều mã khuyến mãi hợp lệ cùng lúc (stack) — mỗi mã phải hợp lệ
     * riêng lẻ (tái dùng findValidByCode), và nếu có từ 2 mã trở lên thì
     * TẤT CẢ phải được đánh dấu stackable=true, tránh khách cộng dồn 2 mã
     * giảm giá không dự tính stack chung (VD 2 mã 50% làm phòng miễn phí).
     *
     * @param  array<int, string>  $codes
     * @return SupportCollection<int, Promotion>
     *
     * @throws ValidationException
     */
    public function findValidManyByCodes(array $codes, ?User $user = null): SupportCollection
    {
        $promotions = collect($codes)->map(fn (string $code) => $this->findValidByCode($code, $user));

        if ($promotions->count() > 1 && $promotions->contains(fn (Promotion $p) => ! $p->stackable)) {
            throw ValidationException::withMessages([
                'promo_codes' => ['Một hoặc nhiều mã không hỗ trợ dùng chung với mã khác — mỗi đơn chỉ được dùng 1 mã không stack.'],
            ]);
        }

        return $promotions;
    }

    /**
     * Xếp hạng danh sách khuyến mãi theo số tiền giảm giảm dần cho một tổng
     * tiền cụ thể — dùng để tự động đề xuất mã "giảm nhiều nhất" lên đầu (VD
     * form Gửi báo giá đoàn/nhóm) thay vì bắt người dùng tự so sánh % vs số
     * tiền cố định.
     *
     * @param  iterable<int, Promotion>  $promotions
     * @return SupportCollection<int, Promotion>
     */
    public function rankForAmount(iterable $promotions, float $amount): SupportCollection
    {
        return collect($promotions)->sortByDesc(fn (Promotion $p) => $p->discountFor($amount))->values();
    }

    public function create(array $data): Promotion
    {
        return Promotion::create($data);
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        $promotion->update($data);

        return $promotion->fresh();
    }

    public function delete(Promotion $promotion): void
    {
        $promotion->delete();
    }

    public function restore(Promotion $promotion): void
    {
        $promotion->restore();
    }
}
