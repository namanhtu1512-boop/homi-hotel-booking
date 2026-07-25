<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseFormRequest;

class AddBookingServicesRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'services'               => ['required', 'array', 'min:1'],
            'services.*.service_id'  => ['required', 'integer', 'distinct', 'exists:services,id'],
            'services.*.quantity'    => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'services.required'              => 'Vui lòng chọn ít nhất một dịch vụ.',
            'services.*.service_id.required' => 'Vui lòng chọn dịch vụ.',
            'services.*.service_id.distinct' => 'Mỗi dịch vụ chỉ được chọn một lần trong 1 lần thêm.',
            'services.*.service_id.exists'   => 'Dịch vụ không tồn tại hoặc đã ngừng cung cấp.',
        ];
    }
}
