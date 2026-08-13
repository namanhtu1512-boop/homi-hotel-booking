<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupDiscountRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'group_discount_policy_id',
        'type',
        'room_count',
        'original_subtotal',
        'requested_percent',
        'requested_amount',
        'approved_percent',
        'approved_amount',
        'staff_cap_percent_snapshot',
        'reason',
        'status',
        'admin_note',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'room_count'                 => 'integer',
        'original_subtotal'          => 'integer',
        'requested_percent'          => 'decimal:2',
        'requested_amount'           => 'integer',
        'approved_percent'           => 'decimal:2',
        'approved_amount'            => 'integer',
        'staff_cap_percent_snapshot' => 'decimal:2',
        'handled_at'                 => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function policy()
    {
        return $this->belongsTo(GroupDiscountPolicy::class, 'group_discount_policy_id');
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
