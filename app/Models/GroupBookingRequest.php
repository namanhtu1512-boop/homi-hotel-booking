<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupBookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'group_size',
        'room_count',
        'check_in',
        'check_out',
        'room_type_ids',
        'message',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    protected $casts = [
        'group_size'    => 'integer',
        'check_in'      => 'date',
        'check_out'     => 'date',
        'room_type_ids' => 'array',
    ];
}
