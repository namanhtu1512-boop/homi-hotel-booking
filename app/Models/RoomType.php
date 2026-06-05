<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'name',
        'slug',
        'description',
        'price_per_night',
        'capacity',
        'bed_type',
        'area',
        'total_rooms',
        'status',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class);
    }
}