<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = ['name', 'icon'];

    public function hotelInfo()
    {
        return $this->belongsToMany(HotelInfo::class, 'hotel_info_amenity', 'amenity_id', 'hotel_id');
    }
}
