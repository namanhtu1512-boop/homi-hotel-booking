<?php

namespace App\Http\Requests\RoomType\Concerns;

/**
 * Tên thuộc tính tiếng Việt dùng chung cho mọi FormRequest liên quan room_types,
 * tránh lặp lại giữa CreateRoomTypeRequest và UpdateRoomTypeRequest.
 */
trait HasRoomTypeAttributes
{
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
            'images_text'     => 'đường dẫn ảnh',
        ];
    }
}
