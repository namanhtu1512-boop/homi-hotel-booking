<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\RoomType\Concerns\HasRoomTypeAttributes;
use App\Http\Requests\RoomType\Concerns\ValidatesImageText;

class UpdateRoomTypeRequest extends BaseFormRequest
{
    use HasRoomTypeAttributes, ValidatesImageText;

    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'price_per_night' => ['sometimes', 'numeric', 'min:0'],
            'capacity'        => ['sometimes', 'integer', 'min:1'],
            'bed_type'        => ['nullable', 'string', 'max:100'],
            'area'            => ['nullable', 'numeric', 'min:0'],
            'total_rooms'     => ['sometimes', 'integer', 'min:1'],
            'images_text'     => ['nullable', 'string', $this->eachImageLineMax500()],
        ];
    }
}
