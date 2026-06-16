<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;

class UpdateRoomTypeRequest extends BaseFormRequest
{
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
            'images'          => ['nullable', 'array'],
            'images.*'        => ['string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'            => 'tên loại phòng',
            'description'     => 'mô tả',
            'price_per_night' => 'giá theo đêm',
            'capacity'        => 'sức chứa',
            'bed_type'        => 'loại giường',
            'area'            => 'diện tích',
            'total_rooms'     => 'tổng số phòng',
            'images'          => 'hình ảnh',
            'images.*'        => 'đường dẫn ảnh',
        ];
    }
}
