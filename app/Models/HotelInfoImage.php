<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HotelInfoImage extends Model
{
    use HasFactory;

    protected $table = 'hotel_info_images';

    protected $fillable = ['hotel_info_id', 'path', 'sort_order'];

    protected $appends = ['image_url'];

    public function hotelInfo()
    {
        return $this->belongsTo(HotelInfo::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

        return asset('storage/' . $this->path);
    }
}
