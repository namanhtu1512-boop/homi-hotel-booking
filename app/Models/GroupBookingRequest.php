<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupBookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'group_size',
        'check_in',
        'check_out',
        'room_type_ids',
        'message',
        'status',
    ];

    protected $casts = [
        'group_size'    => 'integer',
        'check_in'      => 'date',
        'check_out'     => 'date',
        'room_type_ids' => 'array',
    ];
}
