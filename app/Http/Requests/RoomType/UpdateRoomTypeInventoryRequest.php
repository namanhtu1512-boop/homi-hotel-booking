<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\RoomType\Concerns\HasRoomTypeAttributes;

class UpdateRoomTypeInventoryRequest extends BaseFormRequest
{
    use HasRoomTypeAttributes;

    public function rules(): array
    {
        return [
            'total_rooms' => ['required', 'integer', 'min:1'],
        ];
    }
}
