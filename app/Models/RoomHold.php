<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'session_id',
        'check_in',
        'check_out',
        'quantity',
        'expires_at',
    ];

    protected $casts = [
        'check_in'   => 'date',
        'check_out'  => 'date',
        'quantity'   => 'integer',
        'expires_at' => 'datetime',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
