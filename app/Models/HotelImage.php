<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelImage extends Model
{
    use HasFactory;
    protected $fillable = ['hotel_id', 'path', 'sort_order'];

    public function hotel()
    {
        return $this->belongsTo(HotelInfo::class, 'hotel_id');
    }
}
