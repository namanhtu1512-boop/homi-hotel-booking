<?php

namespace App\Services;

use App\Models\SeasonalRate;
use Illuminate\Database\Eloquent\Collection;

class SeasonalRateService
{
    public function list(): Collection
    {
        return SeasonalRate::with('roomType')->latest()->get();
    }

    public function find(int $id): SeasonalRate
    {
        return SeasonalRate::findOrFail($id);
    }

    public function create(array $data): SeasonalRate
    {
        return SeasonalRate::create($data);
    }

    public function update(SeasonalRate $seasonalRate, array $data): SeasonalRate
    {
        $seasonalRate->update($data);

        return $seasonalRate->fresh();
    }

    public function delete(SeasonalRate $seasonalRate): void
    {
        $seasonalRate->delete();
    }

    /**
     * Toàn bộ rate active có thể áp dụng cho 1 room type trong khoảng ngày
     * (nạp 1 lần cho cả booking line, tránh query lại theo từng đêm).
     */
    public function ratesForRoomType(int $roomTypeId, string $checkIn, string $checkOut): Collection
    {
        return SeasonalRate::active()
            ->where(fn ($q) => $q->whereNull('room_type_id')->orWhere('room_type_id', $roomTypeId))
            ->where('start_date', '<', $checkOut)
            ->where('end_date', '>=', $checkIn)
            // Ưu tiên rate riêng cho đúng room_type_id trước rate áp dụng
            // chung (room_type_id null) — PricingService::calculate() lấy
            // rate ĐẦU TIÊN khớp bằng ->first(), nên nếu không sắp xếp thì
            // thứ tự khớp phụ thuộc thứ tự insert trong DB, có thể khiến
            // rate chung "thắng" rate riêng một cách ngẫu nhiên.
            ->orderByRaw('room_type_id IS NULL')
            ->get();
    }

    /**
     * Rate active áp dụng cho 1 ngày cụ thể (mặc định hôm nay) trên danh sách
     * room type — dùng hiển thị badge giá theo mùa ở trang danh sách/chi
     * tiết phòng, để khách biết trước khi vào bước đặt phòng.
     */
    public function activeForDate(array $roomTypeIds, ?string $date = null): Collection
    {
        $date = $date ?? now()->toDateString();

        return SeasonalRate::active()
            ->where(fn ($q) => $q->whereNull('room_type_id')->orWhereIn('room_type_id', $roomTypeIds))
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get();
    }

    /**
     * Toàn bộ rate đang active (không lọc theo ngày) — dùng gửi xuống JS phía
     * form đặt phòng để giá tạm tính client-side tự tra theo từng đêm/loại
     * phòng khách chọn, thay vì chỉ nhân giá gốc như trước.
     */
    public function allActive(): Collection
    {
        return SeasonalRate::active()->get();
    }

    /**
     * Nhãn ngắn gọn cho tag hiển thị trên ảnh phòng (VD "-20%", "-50.000đ")
     * — luôn ngắn, không phụ thuộc độ dài $rate->label (có thể dài tùy ý),
     * tránh tràn/vỡ layout thẻ đè trên ảnh phòng.
     */
    public function shortDiscountLabel(SeasonalRate $rate): string
    {
        $value = (float) $rate->adjustment_value;
        $sign  = $value >= 0 ? '+' : '-';

        return $rate->adjustment_type === 'percent'
            ? $sign . abs($value) . '%'
            : $sign . number_format(abs($value), 0, ',', '.') . 'đ';
    }
}
