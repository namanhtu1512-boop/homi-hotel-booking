<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurchargeItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'price_note',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function incidentalInvoiceItems()
    {
        return $this->hasMany(IncidentalInvoiceItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
