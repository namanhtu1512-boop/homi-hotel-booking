<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupDiscountPolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'min_rooms',
        'discount_percent',
        'status',
    ];

    protected $casts = [
        'min_rooms'        => 'integer',
        'discount_percent' => 'decimal:2',
    ];

    public function groupDiscountRequests()
    {
        return $this->hasMany(GroupDiscountRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
