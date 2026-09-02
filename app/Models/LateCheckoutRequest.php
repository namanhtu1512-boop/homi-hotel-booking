<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LateCheckoutRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'requested_checkout_time',
        'hours_late',
        'fee_amount',
        'reason',
        'room_selections',
        'status',
        'staff_note',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'hours_late'       => 'decimal:2',
        'fee_amount'       => 'decimal:2',
        'room_selections'  => 'array',
        'handled_at'       => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handledByUser()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Các phòng vật lý (BookingItemRoom) khách chọn trả muộn — đơn ĐÃ
     * check-in nên chọn đúng theo phòng thật đang ở, không phải số lượng
     * trừu tượng theo dòng loại phòng. `room_selections` là mảng
     * booking_item_room_id. null/rỗng = TOÀN BỘ phòng đang lưu trú tại thời
     * điểm gọi (tương thích ngược + dùng cho phí tự động không xin phép
     * trước). Cần eager load `booking.bookingItems.bookingItemRooms.room`
     * trước khi gọi để tránh N+1.
     */
    public function selectedBookingItemRooms(): Collection
    {
        $inHouse = $this->booking->inHouseBookingItemRooms();

        if (empty($this->room_selections)) {
            return $inHouse;
        }

        return $inHouse->whereIn('id', $this->room_selections)->values();
    }
}
