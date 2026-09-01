<?php

namespace App\Models;

use App\Models\Concerns\HasRoomSelections;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EarlyCheckinRequest extends Model
{
    use HasFactory, HasRoomSelections;

    protected $fillable = [
        'booking_id',
        'user_id',
        'requested_arrival_time',
        'hours_early',
        'fee_amount',
        'reason',
        'room_selections',
        'status',
        'staff_note',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'hours_early'      => 'integer',
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
}
