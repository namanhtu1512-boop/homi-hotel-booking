<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\RoomType\Concerns\HasRoomTypeAttributes;

class CreateRoomTypeRequest extends BaseFormRequest
{
    use HasRoomTypeAttributes;

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'capacity'        => ['required', 'integer', 'min:1'],
            'bed_type'        => ['nullable', 'string', 'max:100'],
            'area'            => ['nullable', 'numeric', 'min:0'],
            'total_rooms'     => ['required', 'integer', 'min:1'],
            'images'          => ['nullable', 'array'],
            'images.*'        => ['string', 'max:500'],
        ];
    }
}
