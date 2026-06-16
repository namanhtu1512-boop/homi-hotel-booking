<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;

class BookingStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'changed_by',
        'from_status',
        'to_status',
        'note',
    ];

    protected $casts = [
        'from_status' => BookingStatus::class,
        'to_status'   => BookingStatus::class,
        'created_at'  => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
