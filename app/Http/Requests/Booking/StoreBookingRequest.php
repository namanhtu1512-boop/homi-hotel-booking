<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseFormRequest;

class StoreBookingRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'room_type_id'   => ['required', 'integer', 'exists:room_types,id'],
            'check_in'       => ['required', 'date_format:Y-m-d'],
            'check_out'      => ['required', 'date_format:Y-m-d'],
            'quantity'       => ['required', 'integer', 'min:1', 'max:10'],
            'customer_name'  => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:100'],
            'note'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_type_id.required'   => 'Vui lòng chọn loại phòng.',
            'room_type_id.exists'     => 'Loại phòng không tồn tại.',
            'check_in.required'       => 'Vui lòng nhập ngày nhận phòng.',
            'check_in.date_format'    => 'Ngày nhận phòng không đúng định dạng (YYYY-MM-DD).',
            'check_out.required'      => 'Vui lòng nhập ngày trả phòng.',
            'check_out.date_format'   => 'Ngày trả phòng không đúng định dạng (YYYY-MM-DD).',
            'quantity.required'       => 'Vui lòng nhập số phòng cần đặt.',
            'quantity.min'            => 'Số phòng phải ít nhất là 1.',
            'quantity.max'            => 'Số phòng tối đa là 10.',
            'customer_name.required'  => 'Vui lòng nhập tên khách hàng.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại.',
        ];
    }
}
