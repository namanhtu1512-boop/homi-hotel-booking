<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingItemRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_item_id',
        'room_id',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'checked_in_at'  => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function settlement()
    {
        return $this->hasOne(RoomSettlement::class);
    }

    public function bookingServices()
    {
        return $this->hasMany(BookingServiceItem::class);
    }

    public function incidentalInvoiceItems()
    {
        return $this->hasMany(IncidentalInvoiceItem::class);
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function isCheckedOut(): bool
    {
        return $this->checked_out_at !== null;
    }
}
