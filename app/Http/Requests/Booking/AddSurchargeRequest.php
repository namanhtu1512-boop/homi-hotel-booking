<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseFormRequest;

class AddSurchargeRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'note'   => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Vui lòng nhập số tiền phụ phí.',
            'amount.min'       => 'Số tiền phụ phí phải lớn hơn 0.',
            'note.required'    => 'Vui lòng nhập lý do phụ phí.',
        ];
    }
}
