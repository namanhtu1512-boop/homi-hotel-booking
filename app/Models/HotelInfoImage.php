<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelInfoImage extends Model
{
    use HasFactory;

    protected $table = 'hotel_info_images';

    protected $fillable = ['hotel_info_id', 'path', 'sort_order'];

    public function hotelInfo()
    {
        return $this->belongsTo(HotelInfo::class);
    }
}
