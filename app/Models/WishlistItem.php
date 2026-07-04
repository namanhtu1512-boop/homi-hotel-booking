<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_type_id',
        'quantity',
        'adults',
        'children',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'adults'   => 'integer',
        'children' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}
