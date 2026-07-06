# Đặc tả route/form availability — Tuần 9 (Sprint 5)

**Phụ trách:** BE1 (đặc tả route/form đầu vào) — BE3 triển khai `AvailabilityService`.
**File liên quan:**
- [app/Services/AvailabilityService.php](../../app/Services/AvailabilityService.php)
- [app/Services/DateRangeService.php](../../app/Services/DateRangeService.php)
- [app/Http/Controllers/Web/RoomController.php](../../app/Http/Controllers/Web/RoomController.php)
- [app/Http/Controllers/Web/Customer/BookingController.php](../../app/Http/Controllers/Web/Customer/BookingController.php)

**Kiến trúc:** Laravel Blade monolith — route trả thẳng view, không có tầng
`/api`, không trả JSON. Kiểm tra phòng trống được thực hiện bằng cách resubmit
GET kèm query params (không dùng AJAX/JSON).

---

## 1. Route

Không có route JSON riêng cho availability. Việc kiểm tra phòng trống được
gắn vào 2 route GET đã có sẵn, cả hai đều nhận cùng 1 bộ query params:

| Method | Route | Tên route | Auth | Vị trí hiển thị kết quả |
|---|---|---|---|---|
| GET | `/rooms/{id}` | `rooms.show` | Không (public) | Widget "Kiểm tra phòng trống" ở trang chi tiết phòng |
| GET | `/customer/bookings/create` | `customer.bookings.create` | `auth`, `role:customer` | Nút "Kiểm tra phòng trống" trên form đặt phòng |

Không dùng `hotel_id` — Homi chỉ quản lý 1 khách sạn duy nhất.

## 2. Query params đầu vào

| Param | Kiểu | Bắt buộc | Validate (qua `AvailabilityService`) | Ghi chú |
|---|---|---|---|---|
| `room_type_id` | int | Có (path ở `rooms.show`, query ở booking form) | `room_types.status = active`, tồn tại | Không truyền `hotel_id` |
| `check_in` | date `Y-m-d` | Có | không ở quá khứ (`>= hôm nay`) | Ném lỗi tiếng Việt nếu sai |
| `check_out` | date `Y-m-d` | Có | phải sau `check_in` ít nhất 1 đêm | Hai ngày trùng nhau bị từ chối |
| `quantity` | int | Không (mặc định 1) | `>= 1` | Số phòng khách muốn đặt |

Validate được thực hiện trong `DateRangeService::validate()` (gọi qua
`AvailabilityService::check()`), không cần `FormRequest` riêng — theo đúng
pattern đã dùng ở `rooms.show` từ Tuần 8.

## 3. Dữ liệu trả về (đưa vào view)

`AvailabilityService::check()` trả về mảng:

```php
[
    'room_type_id'       => int,
    'check_in'           => string,
    'check_out'          => string,
    'nights'             => int,
    'requested_quantity' => int,
    'available_quantity' => int,   // max(0, total_rooms - booked)
    'can_book'            => bool,  // available_quantity >= requested_quantity
    'total_rooms'         => int,
]
```

Controller không throw lỗi ra HTTP 422 — nếu `check_in`/`check_out` sai định
dạng hoặc ở quá khứ, `ValidationException` được bắt tại controller và hiển
thị trực tiếp trên trang qua biến `$availabilityError` (không redirect, không
làm mất dữ liệu form đã nhập).

## 4. Hành vi UI

- Nếu chưa truyền `check_in`/`check_out` → không hiển thị kết quả, không lỗi.
- Nếu ngày hợp lệ và còn phòng → banner xanh: *"Còn N phòng trống cho X đêm bạn chọn."*
- Nếu ngày hợp lệ nhưng không đủ phòng → banner đỏ: *"Chỉ còn N phòng trống, không đủ cho M phòng yêu cầu."*
- Nếu ngày không hợp lệ → banner đỏ hiển thị đúng message từ `DateRangeService`.

## 5. Phạm vi KHÔNG thuộc tuần này

- Không tạo booking thật (thuộc Tuần 10 — `BookingService::create()` re-check
  availability trong DB transaction trước khi insert).
- Không tính giá (`PricingService`, Tuần 10).
- Không có endpoint JSON/AJAX riêng — đúng định hướng kiến trúc Blade monolith
  của dự án.
