<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_percent',
        'discount_amount',
        'starts_at',
        'ends_at',
        'status',
        'stackable',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'integer',
        'starts_at'        => 'date',
        'ends_at'          => 'date',
        'stackable'        => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isValidNow(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $today = today();

        if ($this->starts_at && $today->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $today->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Số tiền giảm cho một tổng đơn — ưu tiên phần trăm nếu có cả hai.
     */
    public function discountFor(float $totalAmount): float
    {
        // discount_percent cast là 'decimal:2' nên luôn trả về string (VD "0.00"),
        // truthy trong PHP dù giá trị bằng 0 — phải so sánh số thực, không dùng if() trực tiếp.
        if ((float) $this->discount_percent > 0) {
            return round($totalAmount * ((float) $this->discount_percent / 100));
        }

        return min((float) ($this->discount_amount ?? 0), $totalAmount);
    }
}
