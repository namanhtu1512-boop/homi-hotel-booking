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
        'num_children',
        'num_infants',
        'room_count',
        'check_in',
        'check_out',
        'room_type_ids',
        'selected_suggestion',
        'message',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    protected $casts = [
        'group_size'           => 'integer',
        'num_children'         => 'integer',
        'num_infants'          => 'integer',
        'check_in'             => 'date',
        'check_out'            => 'date',
        'room_type_ids'        => 'array',
        'selected_suggestion'  => 'array',
    ];
}
