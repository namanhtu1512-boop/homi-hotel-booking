<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTypeImage extends Model
{
    protected $fillable = ['room_type_id', 'path', 'sort_order'];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}
