<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'room_type_id',
        'user_id',
        'rating',
        'cleanliness_rating',
        'service_rating',
        'value_rating',
        'comment',
        'images',
        'status',
    ];

    protected $casts = [
        'rating'             => 'integer',
        'cleanliness_rating' => 'integer',
        'service_rating'     => 'integer',
        'value_rating'       => 'integer',
        'images'             => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('status', 'visible');
    }
}
