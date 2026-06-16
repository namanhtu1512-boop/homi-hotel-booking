<?php

namespace App\Http\Requests\Hotel;

use App\Http\Requests\BaseFormRequest;

/**
 * AdminListHotelRequest — chuẩn hóa và xác thực query params cho
 * endpoint GET /admin/hotels (danh sách khách sạn phía admin).
 *
 * Params:
 *   search      — tìm theo tên hoặc thành phố (nullable, max 255)
 *   status      — lọc trạng thái: active | hidden | deleted (nullable)
 *   sort_by     — cột sắp xếp: name | city | star_rating | created_at (default: created_at)
 *   sort_order  — chiều sắp xếp: asc | desc (default: desc)
 *   per_page    — số bản ghi mỗi trang: 1–100 (default: 15)
 */
class AdminListHotelRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'search'     => ['nullable', 'string', 'max:255'],
            'status'     => ['nullable', 'in:active,hidden,deleted'],
            'sort_by'    => ['nullable', 'in:name,city,star_rating,created_at'],
            'sort_order' => ['nullable', 'in:asc,desc'],
            'per_page'   => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'search'     => 'từ khóa tìm kiếm',
            'status'     => 'trạng thái',
            'sort_by'    => 'cột sắp xếp',
            'sort_order' => 'chiều sắp xếp',
            'per_page'   => 'số bản ghi mỗi trang',
        ];
    }

    /**
     * Trả về sort_by đã được validate, fallback về 'created_at'.
     */
    public function sortBy(): string
    {
        return $this->input('sort_by', 'created_at') ?? 'created_at';
    }

    /**
     * Trả về sort_order đã được validate, fallback về 'desc'.
     */
    public function sortOrder(): string
    {
        return $this->input('sort_order', 'desc') ?? 'desc';
    }

    /**
     * Trả về per_page đã được validate, fallback về 15.
     */
    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }
}
