<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseFormRequest;

class UpdatePaymentStatusRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:paid,refunded'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái thanh toán.',
            'status.in'        => 'Trạng thái thanh toán không hợp lệ.',
        ];
    }
}
