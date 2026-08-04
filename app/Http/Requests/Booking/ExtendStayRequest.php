<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseFormRequest;

class ExtendStayRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'new_check_out' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_check_out.required'    => 'Vui lòng chọn ngày trả phòng mới.',
            'new_check_out.date_format' => 'Ngày trả phòng mới không hợp lệ.',
        ];
    }
}
