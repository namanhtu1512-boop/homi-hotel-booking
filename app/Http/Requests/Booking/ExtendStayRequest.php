<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseFormRequest;

class ExtendStayRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'new_check_out'        => ['required', 'date_format:Y-m-d'],
            'switch_room_type_id'  => ['nullable', 'integer', 'exists:room_types,id'],
            'switch_room_id'       => ['nullable', 'integer', 'exists:rooms,id', 'required_with:switch_room_type_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_check_out.required'    => 'Vui lòng chọn ngày trả phòng mới.',
            'new_check_out.date_format' => 'Ngày trả phòng mới không hợp lệ.',
            'switch_room_id.required_with' => 'Vui lòng chọn phòng cụ thể cho loại phòng đã đổi.',
        ];
    }
}
