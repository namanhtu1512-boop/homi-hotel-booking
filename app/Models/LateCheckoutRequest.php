<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status',
        'staff_note',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'hours_late'  => 'decimal:2',
        'fee_amount'  => 'decimal:2',
        'handled_at'  => 'datetime',
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
