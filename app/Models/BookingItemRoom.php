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
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'checked_in_at'  => 'datetime',
        'checked_out_at' => 'datetime',
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
