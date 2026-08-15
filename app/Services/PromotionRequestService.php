<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionRequest;
use App\Models\User;
use App\Notifications\NewPromotionRequest;
use App\Notifications\PromotionRequestResolved;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nhân viên đề xuất mã ưu đãi đoàn cho khách quen (không gắn với 1 đơn cụ
 * thể, khác với GroupDiscountRequestService — nhân viên không có quyền tạo
 * khuyến mãi trực tiếp trong hệ thống). Admin duyệt → tự động tạo Promotion
 * thật (kích hoạt ngay, không giới hạn ngày, is_group_promo=true) qua
 * approve(); từ chối → chỉ đánh dấu rejected, không đụng bảng promotions.
 */
class PromotionRequestService
{
    public function myList(int $userId, array $filters = [], string $pageName = 'page'): LengthAwarePaginator
    {
        $query = PromotionRequest::where('user_id', $userId)->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(10, ['*'], $pageName)->withQueryString();
    }

    public function adminList(array $filters = [], string $pageName = 'page'): LengthAwarePaginator
    {
        $query = PromotionRequest::with(['user', 'handledByUser'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(10, ['*'], $pageName)->withQueryString();
    }

    public function pendingCount(): int
    {
        return PromotionRequest::where('status', 'pending')->count();
    }

    public function propose(User $staff, array $data): PromotionRequest
    {
        $code = trim($data['code']);

        if (Promotion::where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => ["Mã \"{$code}\" đã được dùng cho một khuyến mãi khác."],
            ]);
        }

        if (PromotionRequest::where('code', $code)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'code' => ["Mã \"{$code}\" đang có 1 đề xuất khác chờ duyệt."],
            ]);
        }

        $request = PromotionRequest::create([
            'user_id'          => $staff->id,
            'code'             => $code,
            'discount_percent' => $data['discount_percent'],
            'reason'           => $data['reason'] ?? null,
            'status'           => 'pending',
        ]);

        User::where('role', 'admin')->each(fn (User $u) => $u->notify(new NewPromotionRequest($request)));

        return $request;
    }

    /**
     * @return array{request: PromotionRequest, promotion: Promotion}
     */
    public function approve(PromotionRequest $request, User $admin, ?string $adminNote): array
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Đề xuất này đã được xử lý trước đó.'],
            ]);
        }

        if (Promotion::where('code', $request->code)->exists()) {
            throw ValidationException::withMessages([
                'code' => ["Mã \"{$request->code}\" đã được dùng cho một khuyến mãi khác, không thể duyệt — hãy từ chối và yêu cầu nhân viên đề xuất mã khác."],
            ]);
        }

        return DB::transaction(function () use ($request, $admin, $adminNote) {
            $promotion = Promotion::create([
                'name'             => "Ưu đãi khách quen - {$request->code}",
                'code'             => $request->code,
                'description'      => $request->reason,
                'discount_percent' => $request->discount_percent,
                'status'           => 'active',
                'stackable'        => false,
                'is_group_promo'   => true,
            ]);

            $request->update([
                'status'      => 'approved',
                'admin_note'  => $adminNote,
                'promotion_id' => $promotion->id,
                'handled_by'  => $admin->id,
                'handled_at'  => now(),
            ]);

            $request->fresh()->user?->notify(new PromotionRequestResolved($request->fresh(), 'approved'));

            return ['request' => $request->fresh(), 'promotion' => $promotion];
        });
    }

    public function reject(PromotionRequest $request, User $admin, ?string $adminNote): PromotionRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Đề xuất này đã được xử lý trước đó.'],
            ]);
        }

        $request->update([
            'status'     => 'rejected',
            'admin_note' => $adminNote,
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);

        $request->fresh()->user?->notify(new PromotionRequestResolved($request->fresh(), 'rejected'));

        return $request->fresh();
    }
}
