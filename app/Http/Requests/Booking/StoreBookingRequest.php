<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseFormRequest;

class StoreBookingRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'items'                => ['required', 'array', 'min:1'],
            'items.*.room_type_id' => ['required', 'integer', 'distinct', 'exists:room_types,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1', 'max:10'],
            'items.*.adults'       => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.children'     => ['nullable', 'integer', 'min:0', 'max:50'],
            'check_in'             => ['required', 'date_format:Y-m-d'],
            'check_out'            => ['required', 'date_format:Y-m-d'],
            'customer_name'        => ['required', 'string', 'max:100'],
            'customer_phone'       => ['required', 'string', 'max:20'],
            'customer_email'       => ['nullable', 'email', 'max:100'],
            'note'                 => ['nullable', 'string', 'max:500'],
            'promo_code'           => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                => 'Vui lòng chọn ít nhất một loại phòng.',
            'items.min'                     => 'Vui lòng chọn ít nhất một loại phòng.',
            'items.*.room_type_id.required' => 'Vui lòng chọn loại phòng.',
            'items.*.room_type_id.distinct' => 'Mỗi loại phòng chỉ được chọn một lần.',
            'items.*.room_type_id.exists'   => 'Loại phòng không tồn tại.',
            'items.*.quantity.required'     => 'Vui lòng nhập số phòng cần đặt.',
            'items.*.quantity.min'          => 'Số phòng phải ít nhất là 1.',
            'items.*.quantity.max'          => 'Số phòng tối đa là 10 cho mỗi loại.',
            'items.*.adults.required'       => 'Vui lòng nhập số người lớn cho từng loại phòng.',
            'items.*.adults.min'            => 'Mỗi loại phòng phải có ít nhất 1 người lớn.',
            'items.*.children.min'          => 'Số trẻ em không hợp lệ.',
            'check_in.required'             => 'Vui lòng nhập ngày nhận phòng.',
            'check_in.date_format'          => 'Ngày nhận phòng không đúng định dạng (YYYY-MM-DD).',
            'check_out.required'            => 'Vui lòng nhập ngày trả phòng.',
            'check_out.date_format'         => 'Ngày trả phòng không đúng định dạng (YYYY-MM-DD).',
            'customer_name.required'        => 'Vui lòng nhập tên khách hàng.',
            'customer_phone.required'       => 'Vui lòng nhập số điện thoại.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        // Tương thích ngược: payload phẳng room_type_id + quantity (+ adults/
        // children ở cấp đơn, link cũ/client cũ) ⇒ gộp thành 1 dòng items[].
        if (! is_array($items) && $this->filled('room_type_id')) {
            $items = [[
                'room_type_id' => $this->input('room_type_id'),
                'quantity'     => (int) $this->input('quantity', 1),
                'adults'       => $this->input('adults'),
                'children'     => $this->input('children'),
            ]];
        }

        if (is_array($items)) {
            // Số khách được validate theo TỪNG dòng loại phòng (capacity
            // riêng) — thiếu ⇒ mặc định 1 người lớn, 0 trẻ em cho dòng đó.
            $items = array_map(fn (array $item) => [
                ...$item,
                'adults'   => (int) ($item['adults'] ?? 1),
                'children' => (int) ($item['children'] ?? 0),
            ], $items);

            $this->merge(['items' => $items]);
        }
    }
}
