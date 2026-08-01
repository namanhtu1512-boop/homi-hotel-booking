<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentalInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'status',
        'total_amount',
        'paid_at',
        'collected_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_at'      => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function items()
    {
        return $this->hasMany(IncidentalInvoiceItem::class);
    }

    public function collectedByUser()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
