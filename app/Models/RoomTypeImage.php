<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RoomTypeImage extends Model
{
    use HasFactory;
    protected $fillable = ['room_type_id', 'path', 'sort_order'];

    protected $appends = ['image_url'];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

        return asset('storage/' . $this->path);
    }
}
