<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraBedRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'booking_item_id',
        'requested_extra_beds',
        'available_extra_beds',
        'status',
        'resolution',
        'suggested_alternatives',
        'handled_by',
        'handled_at',
        'staff_note',
    ];

    protected $casts = [
        'requested_extra_beds'   => 'integer',
        'available_extra_beds'   => 'integer',
        'suggested_alternatives' => 'array',
        'handled_at'             => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
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
