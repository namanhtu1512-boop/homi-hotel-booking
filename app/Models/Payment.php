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
        'amount_collected',
        'last_gateway_amount',
        'pending_gateway_amount',
        'vnpay_session_expires_at',
        'deposit_amount',
        'surcharge_amount',
        'surcharge_note',
        'status',
        'transaction_code',
        'deposit_transaction_code',
        'gateway_transaction_no',
        'gateway_paid_at',
        'paid_at',
        'deposit_paid_at',
        'note',
        'gateway_order_id',
        'gateway_trans_id',
        'gateway_payload',
    ];

    protected $casts = [
        'amount'                  => 'decimal:2',
        'amount_collected'        => 'decimal:2',
        'last_gateway_amount'     => 'decimal:2',
        'pending_gateway_amount'  => 'decimal:2',
        'deposit_amount'          => 'decimal:2',
        'surcharge_amount'        => 'decimal:2',
        'paid_at'          => 'datetime',
        'deposit_paid_at'  => 'datetime',
        'gateway_paid_at'  => 'datetime',
        'vnpay_session_expires_at' => 'datetime',
        'status'           => PaymentStatus::class,
        'method'           => PaymentMethod::class,
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
