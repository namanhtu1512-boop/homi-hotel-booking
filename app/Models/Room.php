<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'room_number',
        'housekeeping_status',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookingItemRooms()
    {
        return $this->hasMany(BookingItemRoom::class);
    }

    /**
     * Lượt lưu trú đang diễn ra (đã nhận phòng, chưa trả) — dùng cho cột
     * "Nhận / trả phòng" ở trang Phòng vật lý (xem RoomService::list()).
     */
    public function activeStay()
    {
        return $this->hasOne(BookingItemRoom::class)->whereNull('checked_out_at')->latestOfMany('checked_in_at');
    }

    /**
     * Lượt lưu trú gần nhất (kể cả đã trả phòng) — dùng để hiển thị "Đã trả
     * phòng lúc..." khi phòng không còn ai ở nhưng vừa trả phòng trong hôm nay.
     */
    public function lastStay()
    {
        return $this->hasOne(BookingItemRoom::class)->latestOfMany('checked_in_at');
    }

    public function scopeHousekeeping($query, string $status)
    {
        return $query->where('housekeeping_status', $status);
    }

    /**
     * Phòng đang có khách lưu trú — suy ra từ việc đang được gán cho 1
     * booking_item mà booking cha ở trạng thái CHECKED_IN (không lưu cờ
     * occupancy riêng để tránh 2 nguồn sự thật lệch nhau).
     */
    public function isOccupied(): bool
    {
        return $this->bookingItemRooms()
            ->whereHas('bookingItem.booking', fn ($q) => $q->where('status', BookingStatus::CHECKED_IN))
            ->exists();
    }
}
