<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomTypeImage extends Model
{
    use HasFactory;
    protected $fillable = ['room_type_id', 'path', 'sort_order'];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}
