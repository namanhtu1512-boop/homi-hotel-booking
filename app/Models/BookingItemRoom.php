<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingItemRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_item_id',
        'room_id',
    ];

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
