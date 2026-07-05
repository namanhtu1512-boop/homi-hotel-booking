<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'date',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')
            ? $this->cover_image
            : asset('storage/' . $this->cover_image);
    }
}
