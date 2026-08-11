<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseFormRequest;

class AddBookingServiceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:20'],
            'amount'     => ['nullable', 'numeric', 'min:1'],
            'note'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'Vui lòng chọn dịch vụ.',
            'service_id.exists'   => 'Dịch vụ không tồn tại.',
            'quantity.required'   => 'Vui lòng nhập số lượng.',
            'quantity.min'        => 'Số lượng phải ít nhất là 1.',
        ];
    }
}
