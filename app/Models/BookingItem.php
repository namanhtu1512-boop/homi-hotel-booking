<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'room_type_id',
        'quantity',
        'adults',
        'children',
        'price_per_night',
        'nights',
        'subtotal',
        'child_surcharge',
        'price_breakdown',
    ];

    protected $casts = [
        'adults'          => 'integer',
        'children'        => 'integer',
        'price_per_night' => 'decimal:2',
        'subtotal'        => 'decimal:2',
        'child_surcharge' => 'decimal:2',
        'price_breakdown' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function bookingItemRooms()
    {
        return $this->hasMany(BookingItemRoom::class);
    }

    /**
     * Các phòng vật lý cụ thể đã gán cho dòng đơn này khi check-in.
     */
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'booking_item_rooms');
    }
}
