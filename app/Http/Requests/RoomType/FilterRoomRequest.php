<?php

namespace App\Http\Requests\RoomType;

use App\Http\Requests\BaseFormRequest;

/**
 * FilterRoomRequest — chuẩn hóa và xác thực query params cho route công khai
 * GET /rooms (hoặc /customer/rooms) — danh sách/tìm kiếm loại phòng.
 *
 * Homi chỉ quản lý 1 khách sạn duy nhất nên KHÔNG có param location/hotel_id
 * (xem Ke_hoach_Homi mục 2.2 và route trong mục 7).
 *
 * Params:
 *   keyword    — tìm theo tên/mô tả loại phòng (nullable, max 255)
 *   min_price  — giá tối thiểu/đêm (nullable, numeric, >= 0)
 *   max_price  — giá tối đa/đêm (nullable, numeric, >= 0, >= min_price)
 *   capacity   — sức chứa tối thiểu CỦA MỘT PHÒNG (nullable, integer, >= 1)
 *   guests     — tổng số khách của cả đoàn (nullable, integer, >= 1) — dùng
 *                cho thuật toán tìm tổ hợp nhiều phòng (RoomCombinationService),
 *                khác với `capacity` (yêu cầu riêng cho từng phòng)
 *   category   — loại phòng cụ thể (nullable, standard/superior/deluxe/family/suite)
 *   check_in   — ngày nhận phòng dự kiến (nullable, Y-m-d, không ở quá khứ)
 *   check_out  — ngày trả phòng dự kiến (nullable, Y-m-d, sau check_in)
 *
 * check_in/check_out ở bước này chỉ được giữ lại để chuyển tiếp sang form đặt
 * phòng — KHÔNG dùng để loại trừ phòng hết chỗ tại đây (việc đó thuộc
 * AvailabilityService ở Sprint 5). Vì vậy cả hai đều optional, nhưng nếu có
 * một trong hai thì bắt buộc phải có cả cặp và đúng thứ tự.
 */
class FilterRoomRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'keyword'      => ['nullable', 'string', 'max:255'],
            'min_price'    => ['nullable', 'numeric', 'min:0'],
            'max_price'    => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'capacity'     => ['nullable', 'integer', 'min:1'],
            'guests'       => ['nullable', 'integer', 'min:1', 'max:8'],
            'category'     => ['nullable', 'in:standard,superior,deluxe,family,suite'],
            'quantity'     => ['nullable', 'integer', 'min:1', 'max:4'],
            'bed_type'     => ['nullable', 'string', 'max:100'],
            'sort'         => ['nullable', 'in:price_asc,price_desc,rating,newest,popularity,best_match'],
            'check_in'     => ['nullable', 'date_format:Y-m-d', 'after_or_equal:' . $this->todayVn(), 'required_with:check_out'],
            'check_out'    => ['nullable', 'date_format:Y-m-d', 'after:check_in', 'required_with:check_in'],
        ];
    }

    public function attributes(): array
    {
        return [
            'keyword'     => 'từ khóa tìm kiếm',
            'min_price'   => 'giá tối thiểu',
            'max_price'   => 'giá tối đa',
            'capacity'    => 'sức chứa',
            'guests'      => 'số khách',
            'category'    => 'loại phòng',
            'check_in'    => 'ngày nhận phòng',
            'check_out'   => 'ngày trả phòng',
        ];
    }

    public function messages(): array
    {
        return [
            'max_price.gte'        => 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.',
            'check_in.after_or_equal' => 'Ngày nhận phòng không được ở quá khứ.',
            'check_in.required_with'  => 'Vui lòng chọn ngày nhận phòng.',
            'check_out.after'         => 'Ngày trả phòng phải sau ngày nhận phòng.',
            'check_out.required_with' => 'Vui lòng chọn ngày trả phòng.',
        ];
    }

    /**
     * Từ khóa tìm kiếm đã trim, null nếu rỗng.
     */
    public function keyword(): ?string
    {
        return $this->trimmedString('keyword');
    }

    /**
     * true nếu request có truyền cặp ngày nhận/trả phòng hợp lệ để chuyển
     * tiếp sang form đặt phòng.
     */
    public function hasDateRange(): bool
    {
        return $this->filled('check_in') && $this->filled('check_out');
    }
}
