<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'current_room_type_id',
        'requested_room_type_id',
        'current_check_in',
        'current_check_out',
        'requested_check_in',
        'requested_check_out',
        'reason',
        'status',
        'staff_note',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'current_check_in'    => 'date',
        'current_check_out'   => 'date',
        'requested_check_in'  => 'date',
        'requested_check_out' => 'date',
        'handled_at'          => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
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
