<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * DateRangeService — dùng chung cho AvailabilityService và BookingService.
 *
 * Quy tắc ngày lưu trú của Homi:
 *  - check_in  >= hôm nay
 *  - check_out >  check_in  (tối thiểu 1 đêm)
 *  - Hai ngày giống nhau (0 đêm) bị từ chối
 */
class DateRangeService
{
    /**
     * Validate cặp ngày nhận/trả phòng.
     * Ném ValidationException với message tiếng Việt nếu không hợp lệ.
     *
     * @throws ValidationException
     */
    public function validate(string $checkIn, string $checkOut): void
    {
        $in  = Carbon::parse($checkIn)->startOfDay();
        $out = Carbon::parse($checkOut)->startOfDay();

        if ($in->lt($this->todayVn())) {
            throw ValidationException::withMessages([
                'check_in' => ['Ngày nhận phòng không được trước hôm nay.'],
            ]);
        }

        if ($out->lte($in)) {
            throw ValidationException::withMessages([
                'check_out' => ['Ngày trả phòng phải sau ngày nhận phòng ít nhất 1 đêm.'],
            ]);
        }
    }

    /**
     * Tính số đêm giữa check_in và check_out.
     */
    public function nightCount(string $checkIn, string $checkOut): int
    {
        return (int) Carbon::parse($checkIn)->startOfDay()->diffInDays(
            Carbon::parse($checkOut)->startOfDay()
        );
    }

    /**
     * Kiểm tra hai khoảng ngày có giao nhau không.
     *
     * Điều kiện giao: existingIn < newOut AND existingOut > newIn
     * Hai khoảng sát nhau (T6 trả / T7 nhận) KHÔNG tính là giao.
     */
    public function overlaps(
        string $existingCheckIn,
        string $existingCheckOut,
        string $newCheckIn,
        string $newCheckOut
    ): bool {
        $eIn  = Carbon::parse($existingCheckIn)->startOfDay();
        $eOut = Carbon::parse($existingCheckOut)->startOfDay();
        $nIn  = Carbon::parse($newCheckIn)->startOfDay();
        $nOut = Carbon::parse($newCheckOut)->startOfDay();

        return $eIn->lt($nOut) && $eOut->gt($nIn);
    }

    /**
     * Parse ngày thành Carbon instance (startOfDay).
     */
    public function parse(string $date): CarbonInterface
    {
        return Carbon::parse($date)->startOfDay();
    }

    /**
     * "Hôm nay" theo giờ Việt Nam, quy về ngày lịch thuần túy ở timezone mặc
     * định của app (UTC — xem config/app.php) để so sánh an toàn với $in/$out
     * (cũng được parse ở timezone mặc định). Không dùng Carbon::today() trực
     * tiếp: nó lấy "hôm nay" theo UTC, nên trong khung 00:00–06:59 giờ VN mỗi
     * ngày, UTC vẫn còn là NGÀY HÔM TRƯỚC — khiến check_in = "hôm qua" (giờ
     * VN) bị coi là hợp lệ. Cùng pattern với Booking::todayForCheckoutComparison().
     */
    private function todayVn(): CarbonInterface
    {
        return Carbon::createFromFormat('Y-m-d', Carbon::now('Asia/Ho_Chi_Minh')->toDateString())->startOfDay();
    }
}
