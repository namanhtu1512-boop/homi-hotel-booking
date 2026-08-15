<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'discount_percent',
        'reason',
        'status',
        'admin_note',
        'promotion_id',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'handled_at'       => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handledByUser()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
