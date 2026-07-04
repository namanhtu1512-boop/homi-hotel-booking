<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'method',
        'amount',
        'deposit_amount',
        'status',
        'transaction_code',
        'deposit_transaction_code',
        'paid_at',
        'deposit_paid_at',
        'note',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'deposit_amount'  => 'decimal:2',
        'paid_at'         => 'datetime',
        'deposit_paid_at' => 'datetime',
        'status'          => PaymentStatus::class,
        'method'          => PaymentMethod::class,
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(PaymentStatusLog::class)->orderBy('created_at');
    }

    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }

    public function canRefund(): bool
    {
        return $this->status->canRefund();
    }
}
