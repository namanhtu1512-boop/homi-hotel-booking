<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;

class UpdateRoomTypePriceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'price_per_night' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'price_per_night' => 'giá theo đêm',
        ];
    }
}
