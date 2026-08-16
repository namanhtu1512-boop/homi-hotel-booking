<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItemGuest extends Model
{
    protected $fillable = [
        'booking_item_id',
        'full_name',
        'id_number',
    ];

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function isComplete(): bool
    {
        return filled($this->full_name) && filled($this->id_number);
    }
}
