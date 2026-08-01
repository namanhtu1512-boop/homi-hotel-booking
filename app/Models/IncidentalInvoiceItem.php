<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentalInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'incidental_invoice_id',
        'type',
        'description',
        'amount',
        'booking_service_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function incidentalInvoice()
    {
        return $this->belongsTo(IncidentalInvoice::class);
    }

    public function bookingService()
    {
        return $this->belongsTo(BookingServiceItem::class, 'booking_service_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
