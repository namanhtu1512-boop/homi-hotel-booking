<?php

namespace App\Services;

use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class GroupBookingRequestService
{
    public function __construct(
        private readonly PromotionService $promotionService,
    ) {}

    public function create(array $data): GroupBookingRequest
    {
        return GroupBookingRequest::create($data);
    }

    public function adminList(array $filters = []): LengthAwarePaginator
    {
        $query = GroupBookingRequest::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    public function markContacted(GroupBookingRequest $request): GroupBookingRequest
    {
        // Không hạ cấp trạng thái 'converted' về lại 'contacted' — 1 yêu cầu
        // đã chuyển thành đơn đặt phòng thì giữ nguyên trạng thái converted.
        if ($request->status === 'converted') {
            return $request;
        }

        $request->update(['status' => 'contacted']);

        return $request->fresh();
    }

    /**
     * Đánh dấu yêu cầu đã được chuyển thành đơn đặt phòng thật (converted).
     * Trạng thái cuối trong vòng đời yêu cầu — khác 'contacted' để UI biết
     * không cho tạo đơn thêm lần nữa từ cùng 1 yêu cầu (tránh đơn trùng).
     */
    public function markConverted(GroupBookingRequest $request): GroupBookingRequest
    {
        $request->update(['status' => 'converted']);

        return $request->fresh();
    }

    public function delete(GroupBookingRequest $request): void
    {
        $request->delete();
    }

    /**
     * Dòng gợi ý mặc định cho form "Tạo đơn"/"Gửi báo giá" — ưu tiên đúng số
     * lượng phòng theo phương án khách đã chọn (selected_suggestion.rooms),
     * vì đó là con số khách nhìn thấy khi gửi yêu cầu. Chỉ rơi về room_type_ids
     * (mỗi loại 1 phòng) khi khách gửi yêu cầu mà chưa chọn phương án nào.
     */
    public function defaultPrefillItems(GroupBookingRequest $request): array
    {
        $rooms = $request->selected_suggestion['rooms'] ?? null;

        if ($rooms) {
            return array_map(fn (array $r) => [
                'room_type_id' => $r['room_type_id'] ?? '',
                'quantity'     => $r['quantity'] ?? 1,
                'adults'       => $r['occupancy_each'] ?? 2,
                'children'     => 0,
                'infants'      => 0,
            ], $rooms);
        }

        if ($request->room_type_ids) {
            return array_map(fn ($id) => [
                'room_type_id' => $id,
                'quantity'     => 1,
                'adults'       => 2,
                'children'     => 0,
                'infants'      => 0,
            ], $request->room_type_ids);
        }

        return [['room_type_id' => '', 'quantity' => 1, 'adults' => 2, 'children' => 0, 'infants' => 0]];
    }

    /**
     * Ước tính tổng tiền báo giá dựa trên dòng gợi ý mặc định
     * (defaultPrefillItems) và giá phòng hiện tại — chỉ dùng để xếp hạng mã
     * giảm giá "giảm nhiều nhất" cho admin/staff xem trước khi vào form thật
     * (buildQuote), KHÔNG phải giá cuối cùng gửi khách vì admin/staff có thể
     * đổi số phòng/giá trước khi bấm "Gửi báo giá".
     */
    public function estimateQuoteTotal(GroupBookingRequest $request, array $prefillItems, Collection $roomTypes): float
    {
        $items = collect($prefillItems)
            ->filter(fn (array $row) => ! empty($row['room_type_id']))
            ->map(fn (array $row) => [
                'room_type_id'    => $row['room_type_id'],
                'quantity'        => $row['quantity'] ?? 1,
                'price_per_night' => $roomTypes->firstWhere('id', $row['room_type_id'])?->price_per_night ?? 0,
            ])
            ->values()
            ->all();

        if (! $items) {
            return 0.0;
        }

        return (float) $this->buildQuote($request, ['quote_items' => $items])['total'];
    }

    /**
     * Dựng báo giá từ dữ liệu form "Gửi báo giá" (đã validate) — dùng chung
     * cho cả gửi chat (đoạn text $lines) và gửi email (mảng $items có cấu
     * trúc để render bảng HTML), tránh lặp lại logic tính tiền ở 2 kênh.
     */
    public function buildQuote(GroupBookingRequest $groupRequest, array $data): array
    {
        $roomTypes = RoomType::whereIn('id', array_column($data['quote_items'], 'room_type_id'))->get()->keyBy('id');

        // max(1, ...) — nếu khách chọn check_in = check_out (0 đêm theo diffInDays)
        // vẫn tính tối thiểu 1 đêm, tránh báo giá 0đ vô nghĩa.
        $nights = ($groupRequest->check_in && $groupRequest->check_out)
            ? max(1, $groupRequest->check_in->diffInDays($groupRequest->check_out))
            : null;

        $lines = ["**Báo giá đặt phòng đoàn/nhóm** (Yêu cầu #{$groupRequest->id})"];
        if ($nights) $lines[] = "Thời gian: {$groupRequest->check_in->format('d/m/Y')} → {$groupRequest->check_out->format('d/m/Y')} ({$nights} đêm)";

        $items = [];
        $total = 0;
        foreach ($data['quote_items'] as $item) {
            $name     = $roomTypes[$item['room_type_id']]?->name ?? '?';
            $subtotal = $item['quantity'] * $item['price_per_night'] * ($nights ?? 1);
            $total   += $subtotal;
            $items[]  = [
                'name'            => $name,
                'quantity'        => $item['quantity'],
                'price_per_night' => $item['price_per_night'],
                'subtotal'        => $subtotal,
            ];
            $lines[]  = "- {$name}: {$item['quantity']} phòng × " . number_format($item['price_per_night'], 0, ',', '.') . 'đ/đêm'
                . ($nights ? ' = ' . number_format($subtotal, 0, ',', '.') . 'đ' : '');
        }

        // Phụ thu giường phụ (trẻ em 6-11 tuổi) — nhân viên tự nhập số giường
        // và giá, KHÔNG tự động suy ra từ num_children của yêu cầu đoàn, vì
        // chỉ loại phòng có RoomType::supportsExtraBed() = true mới hỗ trợ
        // giường phụ thật — form này không biết nhân viên đã chọn đúng loại
        // phòng phù hợp hay chưa, để nhân viên tự quyết (blade cảnh báo qua
        // updateExtraBedWarning() đọc động data-supports-extra-bed).
        $extraBeds        = (int) ($data['extra_beds'] ?? 0);
        $extraBedPrice    = (float) ($data['extra_bed_price_per_night'] ?? 0);
        $extraBedSubtotal = 0;
        if ($extraBeds > 0) {
            $extraBedSubtotal = $extraBeds * $extraBedPrice * ($nights ?? 1);
            $total           += $extraBedSubtotal;
            $lines[]          = "- Giường phụ trẻ em (6-11 tuổi): {$extraBeds} giường × " . number_format($extraBedPrice, 0, ',', '.') . 'đ/đêm'
                . ($nights ? ' = ' . number_format($extraBedSubtotal, 0, ',', '.') . 'đ' : '');
        }

        // Mã giảm giá (nếu có) — trừ tuần tự vào phần còn lại, giống hệt cách
        // BookingService::createByAdmin() áp mã khi tạo đơn thật (quá 1 mã thì
        // TẤT CẢ phải stackable — PromotionService::findValidManyByCodes()),
        // để hành vi tính giảm giá nhất quán giữa báo giá và đơn thật. Báo giá
        // này chưa gắn vào 1 đơn thật nên không ghi booking_promotions ở đây.
        $subtotalBeforeDiscount = $total;
        $discount               = 0;
        $appliedPromotions      = [];
        $promoCodes             = $data['promo_codes'] ?? [];
        if ($promoCodes) {
            $promotions = $this->promotionService->findValidManyByCodes($promoCodes, $groupRequest->user);
            $lines[]    = "Tạm tính: " . number_format($subtotalBeforeDiscount, 0, ',', '.') . 'đ';
            $remaining  = $subtotalBeforeDiscount;
            foreach ($promotions as $promotion) {
                $lineDiscount = min((int) $promotion->discountFor($remaining), $remaining);
                $discount    += $lineDiscount;
                $remaining   -= $lineDiscount;
                $appliedPromotions[] = ['code' => $promotion->code, 'discount_amount' => $lineDiscount];
                $lines[] = "- Mã giảm giá {$promotion->code}: -" . number_format($lineDiscount, 0, ',', '.') . 'đ';
            }
        }
        $total -= $discount;

        if ($nights) $lines[] = "**Tổng dự kiến: " . number_format($total, 0, ',', '.') . 'đ** (chưa bao gồm dịch vụ phát sinh)';
        $note = $data['note'] ?? null;
        if ($note) $lines[] = "\n{$note}";
        $lines[] = "\nXem phòng và đặt ngay: " . route('rooms.index');

        return [
            'lines'                     => $lines,
            'items'                     => $items,
            'nights'                    => $nights,
            'extra_beds'                => $extraBeds,
            'extra_bed_price_per_night' => $extraBedPrice,
            'extra_bed_subtotal'        => $extraBedSubtotal,
            'subtotal'                  => $subtotalBeforeDiscount,
            'discount_amount'           => $discount,
            'applied_promotions'        => $appliedPromotions,
            'total'                     => $total,
            'note'                      => $note,
        ];
    }
}
