<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_per_night',
        'capacity',
        'bed_type',
        'area',
        'total_rooms',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'capacity'        => 'integer',
        'area'            => 'decimal:2',
        'total_rooms'     => 'integer',
        'status'          => 'string',
        'is_featured'     => 'boolean',
    ];

    // --- Relationships ---

    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function images()
    {
        return $this->hasMany(RoomTypeImage::class)->orderBy('sort_order');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'room_type_amenity');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}