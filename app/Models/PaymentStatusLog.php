<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class PaymentStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'changed_by',
        'from_status',
        'to_status',
        'note',
    ];

    protected $casts = [
        'from_status' => PaymentStatus::class,
        'to_status'   => PaymentStatus::class,
        'created_at'  => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
