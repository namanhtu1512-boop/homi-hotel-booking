<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseFormRequest;

class CreateWalkInBookingRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'check_in'              => ['required', 'date', 'after_or_equal:today'],
            'check_out'             => ['required', 'date', 'after:check_in'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.room_type_id'  => ['required', 'integer', 'distinct', 'exists:room_types,id'],
            'items.*.quantity'      => ['required', 'integer', 'min:1', 'max:10'],
            'items.*.adults'        => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.children'      => ['nullable', 'integer', 'min:0', 'max:50'],
            'items.*.infants'       => ['nullable', 'integer', 'min:0', 'max:50'],
            'customer_name'         => ['required', 'string', 'max:100'],
            'customer_phone'        => ['required', 'string', 'max:20'],
            'customer_email'        => ['nullable', 'email', 'max:150'],
            'note'                  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.after_or_equal'       => 'Ngày nhận phòng không được ở quá khứ.',
            'check_out.after'               => 'Ngày trả phòng phải sau ngày nhận phòng.',
            'items.required'                => 'Vui lòng chọn ít nhất một loại phòng.',
            'items.min'                     => 'Vui lòng chọn ít nhất một loại phòng.',
            'items.*.room_type_id.required' => 'Vui lòng chọn loại phòng.',
            'items.*.room_type_id.distinct' => 'Mỗi loại phòng chỉ được chọn một lần.',
            'items.*.room_type_id.exists'   => 'Loại phòng không tồn tại.',
            'items.*.quantity.required'     => 'Vui lòng nhập số phòng cần đặt.',
            'items.*.quantity.min'          => 'Số phòng phải ít nhất là 1.',
            'items.*.adults.required'       => 'Vui lòng nhập số người lớn cho từng loại phòng.',
            'customer_name.required'        => 'Vui lòng nhập tên khách hàng.',
            'customer_phone.required'       => 'Vui lòng nhập số điện thoại.',
        ];
    }
}
