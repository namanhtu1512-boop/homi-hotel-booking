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
