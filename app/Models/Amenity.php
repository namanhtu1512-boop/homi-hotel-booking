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

    public function roomTypes()
    {
        return $this->belongsToMany(RoomType::class, 'room_type_amenity');
    }
}
