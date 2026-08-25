<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentalInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'incidental_invoice_id',
        'booking_item_room_id',
        'type',
        'description',
        'amount',
        'quantity',
        'booking_service_id',
        'surcharge_item_id',
        'created_by',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function incidentalInvoice()
    {
        return $this->belongsTo(IncidentalInvoice::class);
    }

    public function bookingService()
    {
        return $this->belongsTo(BookingServiceItem::class, 'booking_service_id');
    }

    public function surchargeItem()
    {
        return $this->belongsTo(SurchargeItem::class);
    }

    public function bookingItemRoom()
    {
        return $this->belongsTo(BookingItemRoom::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
