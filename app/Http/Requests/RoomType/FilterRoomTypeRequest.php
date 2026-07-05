<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;

/**
 * BE1 Tuần 7 — Query params chuẩn cho trang danh sách phòng công khai.
 * Chỉ 1 khách sạn nên không có location/hotel_id.
 * check_in/check_out được validate và truyền xuống view nhưng CHƯA dùng
 * để lọc phòng trống (chức năng đó thuộc Tuần 9).
 */
class FilterRoomTypeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword'   => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'capacity'  => ['nullable', 'integer', 'min:1', 'max:10'],
            'quantity'  => ['nullable', 'integer', 'min:1', 'max:10'],
            'check_in'  => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['nullable', 'date_format:Y-m-d', 'after:check_in'],
            'per_page'  => ['nullable', 'integer', 'min:3', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('max_price', 'gte:min_price', fn ($input) =>
            $input->min_price !== null && $input->max_price !== null
        );
    }

    public function messages(): array
    {
        return [
            'min_price.min'           => 'Giá tối thiểu không được âm.',
            'max_price.min'           => 'Giá tối đa không được âm.',
            'max_price.gte'           => 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.',
            'capacity.min'            => 'Sức chứa tối thiểu là 1 khách.',
            'capacity.max'            => 'Sức chứa tối đa là 10 khách.',
            'check_in.date_format'    => 'Ngày nhận phòng không đúng định dạng (YYYY-MM-DD).',
            'check_in.after_or_equal' => 'Ngày nhận phòng không được trước hôm nay.',
            'check_out.date_format'   => 'Ngày trả phòng không đúng định dạng (YYYY-MM-DD).',
            'check_out.after'         => 'Ngày trả phòng phải sau ngày nhận phòng.',
        ];
    }

    public function filters(): array
    {
        return $this->only(['keyword', 'min_price', 'max_price', 'capacity', 'quantity', 'check_in', 'check_out']);
    }
}
