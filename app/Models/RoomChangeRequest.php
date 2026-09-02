<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'booking_item_id',
        'quantity',
        'user_id',
        'current_room_type_id',
        'requested_room_type_id',
        'current_check_in',
        'current_check_out',
        'requested_check_in',
        'requested_check_out',
        'price_delta',
        'reason',
        'status',
        'staff_note',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'quantity'             => 'integer',
        'current_check_in'    => 'date',
        'current_check_out'   => 'date',
        'requested_check_in'  => 'date',
        'requested_check_out' => 'date',
        'price_delta'         => 'decimal:2',
        'handled_at'          => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentRoomType()
    {
        return $this->belongsTo(RoomType::class, 'current_room_type_id');
    }

    public function requestedRoomType()
    {
        return $this->belongsTo(RoomType::class, 'requested_room_type_id');
    }

    public function handledByUser()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
