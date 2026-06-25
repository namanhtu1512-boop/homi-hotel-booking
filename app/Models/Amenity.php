<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = ['name', 'icon'];

    public function hotelInfos()
    {
        return $this->belongsToMany(HotelInfo::class, 'hotel_info_amenity');
    }
}
