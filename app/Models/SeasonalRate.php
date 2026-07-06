<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeasonalRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'label',
        'start_date',
        'end_date',
        'adjustment_type',
        'adjustment_value',
        'status',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'adjustment_value' => 'decimal:2',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Rate áp dụng cho $date nếu: đang active, nằm trong khoảng ngày, và
     * (áp dụng cho tất cả loại phòng HOẶC đúng room_type_id được truyền vào).
     */
    public function appliesTo(int $roomTypeId, \Carbon\Carbon $date): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($date->lt($this->start_date) || $date->gt($this->end_date)) {
            return false;
        }

        return $this->room_type_id === null || $this->room_type_id === $roomTypeId;
    }
}
