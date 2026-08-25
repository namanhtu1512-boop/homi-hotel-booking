<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'booking_item_room_id',
        'room_charge',
        'service_charge',
        'deposit_credit',
        'amount_due',
        'amount_collected',
        'method',
        'note',
        'collected_by',
        'collected_at',
    ];

    protected $casts = [
        'room_charge'      => 'decimal:2',
        'service_charge'   => 'decimal:2',
        'deposit_credit'   => 'decimal:2',
        'amount_due'       => 'decimal:2',
        'amount_collected' => 'decimal:2',
        'collected_at'     => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingItemRoom()
    {
        return $this->belongsTo(BookingItemRoom::class);
    }

    public function collectedByUser()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
