<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'valid_from',
        'valid_to',
        'hotel_id',
        'status',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to'   => 'date',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function isCurrentlyValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $today = now()->toDateString();

        return (! $this->valid_from || $this->valid_from->toDateString() <= $today)
            && (! $this->valid_to || $this->valid_to->toDateString() >= $today);
    }
}
