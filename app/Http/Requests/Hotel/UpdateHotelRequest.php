<?php

namespace App\Http\Requests\Hotel;

use App\Http\Requests\BaseFormRequest;

class UpdateHotelRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name'           => ['sometimes', 'string', 'max:255'],
            'city'           => ['sometimes', 'string', 'max:100'],
            'district'       => ['nullable', 'string', 'max:100'],
            'address'        => ['sometimes', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'star_rating'    => ['nullable', 'integer', 'between:1,5'],
            'amenity_ids'    => ['nullable', 'array'],
            'amenity_ids.*'  => ['integer', 'exists:amenities,id'],
            'images'         => ['nullable', 'array'],
            'images.*'       => ['string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'          => 'tên khách sạn',
            'city'          => 'thành phố',
            'district'      => 'quận/huyện',
            'address'       => 'địa chỉ',
            'description'   => 'mô tả',
            'star_rating'   => 'xếp hạng sao',
            'amenity_ids'   => 'tiện ích',
            'amenity_ids.*' => 'tiện ích',
            'images'        => 'hình ảnh',
            'images.*'      => 'đường dẫn ảnh',
        ];
    }
}
