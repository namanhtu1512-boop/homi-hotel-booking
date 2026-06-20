<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * HotelInfo — dữ liệu singleton của khách sạn duy nhất mà Homi quản lý.
 * Luôn chỉ có đúng 1 bản ghi (id = 1). Không có create/list/delete nhiều bản ghi.
 */
class HotelInfo extends Model
{
    use HasFactory;

    protected $table = 'hotel_info';

    protected $fillable = [
        'name',
        'description',
        'address',
        'hotline',
        'email',
        'check_in_time',
        'check_out_time',
        'policies',
        'star_rating',
        'is_open',
    ];

    protected $casts = [
        'star_rating' => 'integer',
        'is_open'     => 'boolean',
    ];

    // --- Relationships ---

    public function images()
    {
        return $this->hasMany(HotelImage::class, 'hotel_id')->orderBy('sort_order');
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'hotel_info_amenity', 'hotel_id', 'amenity_id');
    }
}
