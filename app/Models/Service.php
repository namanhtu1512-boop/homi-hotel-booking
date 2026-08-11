<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'price_note',
        'group',
        'status',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function bookingServiceItems()
    {
        return $this->hasMany(BookingServiceItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Có giới hạn khung giờ phục vụ hay không — cả 2 cột đều để trống nghĩa
     * là phục vụ cả ngày (VD: khăn tắm thêm, giặt ủi).
     */
    public function hasTimeWindow(): bool
    {
        return $this->available_from !== null && $this->available_until !== null;
    }

    /**
     * Dịch vụ có đang trong khung giờ phục vụ tại thời điểm $time hay không.
     * Không giới hạn overnight (VD 22:00-02:00) — chỉ so sánh trong cùng 1
     * ngày, đủ dùng cho phạm vi đồ án.
     *
     * $time mặc định giờ Việt Nam (Asia/Ho_Chi_Minh) khi không truyền vào —
     * available_from/available_until được admin nhập theo giờ địa phương,
     * trong khi app chạy múi giờ UTC (config/app.php), nên KHÔNG được dùng
     * now()/Carbon mặc định (UTC) để so sánh, nếu không sẽ lệch 7 tiếng.
     */
    public function isAvailableAt(?Carbon $time = null): bool
    {
        if (! $this->hasTimeWindow()) {
            return true;
        }

        $time ??= now('Asia/Ho_Chi_Minh');
        $current = $time->format('H:i:s');

        return $current >= $this->available_from && $current <= $this->available_until;
    }

    /**
     * Nhãn khung giờ để hiển thị (VD "06:00-10:00"), rỗng nếu phục vụ cả ngày.
     */
    public function availabilityLabel(): ?string
    {
        if (! $this->hasTimeWindow()) {
            return null;
        }

        return substr($this->available_from, 0, 5) . '-' . substr($this->available_until, 0, 5);
    }
}
