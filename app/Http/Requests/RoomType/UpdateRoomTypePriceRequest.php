<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\RoomType\Concerns\HasRoomTypeAttributes;

class UpdateRoomTypePriceRequest extends BaseFormRequest
{
    use HasRoomTypeAttributes;

    public function rules(): array
    {
        return [
            'price_per_night' => ['required', 'numeric', 'min:0'],
        ];
    }
}
