<?php

namespace App\Http\Requests\Hotel;

use App\Http\Requests\BaseFormRequest;

class UpdateHotelInfoRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'max:255'],
            'address'         => ['sometimes', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'check_in_time'   => ['nullable', 'date_format:H:i'],
            'check_out_time'  => ['nullable', 'date_format:H:i'],
            'policies'        => ['nullable', 'string', 'max:5000'],
            'star_rating'     => ['nullable', 'integer', 'between:1,5'],
            'amenity_ids'     => ['nullable', 'array'],
            'amenity_ids.*'   => ['integer', 'exists:amenities,id'],
            'images'          => ['nullable', 'array'],
            'images.*'        => ['string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'           => 'tên khách sạn',
            'address'        => 'địa chỉ',
            'description'    => 'mô tả',
            'check_in_time'  => 'giờ nhận phòng',
            'check_out_time' => 'giờ trả phòng',
            'policies'       => 'chính sách',
            'star_rating'    => 'xếp hạng sao',
            'amenity_ids'    => 'tiện ích',
            'amenity_ids.*'  => 'tiện ích',
            'images'         => 'hình ảnh',
            'images.*'       => 'đường dẫn ảnh',
        ];
    }
}
