<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;

class UpdateRoomTypeInventoryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'total_rooms' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'total_rooms' => 'tổng số phòng',
        ];
    }
}
