# Đặc tả route search/list phòng — Tuần 7 (Sprint 4)

**Phụ trách:** BE1 (FilterRoomRequest, đặc tả route) — BE2 triển khai controller/view dùng đặc tả này.
**File rule:** [app/Http/Requests/RoomType/FilterRoomRequest.php](../../app/Http/Requests/RoomType/FilterRoomRequest.php)
**Kiến trúc:** Laravel Blade monolith — route trả thẳng view, không có tầng `/api`, không trả JSON.

---

## 1. Route

| Method | Route | Tên route gợi ý | Auth | Mô tả |
|---|---|---|---|---|
| GET | `/rooms` | `rooms.index` | Không (public) | Danh sách/tìm kiếm loại phòng đang `active` |

Theo kế hoạch (mục 7), route public dùng `/rooms` (không đặt trong nhóm
`/customer` vì nhóm đó đang được bảo vệ bởi middleware `auth + role:customer`
— khách chưa đăng nhập vẫn phải xem/lọc được phòng). `/customer/rooms` chỉ nên
dùng làm alias hiển thị nếu cần, không thay thế route public này.

Không có `hotel_id`/`location` trong route hay query — Homi chỉ quản lý 1
khách sạn duy nhất (`hotel_info` là singleton).

## 2. Query params (FilterRoomRequest)

| Param | Kiểu | Bắt buộc | Validate | Ghi chú |
|---|---|---|---|---|
| `keyword` | string | Không | `max:255` | Tìm theo tên/mô tả loại phòng |
| `min_price` | numeric | Không | `min:0` | Giá tối thiểu/đêm |
| `max_price` | numeric | Không | `min:0`, `gte:min_price` | Giá tối đa/đêm |
| `amenities[]` | array<int> | Không | mỗi phần tử `exists:amenities,id` | Lọc theo tiện ích |
| `capacity` | int | Không | `min:1` | Sức chứa tối thiểu |
| `check_in` | date | Không* | `date_format:Y-m-d`, `after_or_equal:today` | Bắt buộc nếu có `check_out` |
| `check_out` | date | Không* | `date_format:Y-m-d`, `after:check_in` | Bắt buộc nếu có `check_in` |

`check_in`/`check_out` ở route này **chỉ được giữ lại** để chuyển tiếp dữ liệu
sang form đặt phòng (`/customer/booking/create`) — **không** dùng để loại trừ
phòng hết chỗ. Việc kiểm tra phòng trống thật sự thuộc `AvailabilityService`,
triển khai ở Sprint 5 (Tuần 9). Vì vậy danh sách phòng ở Sprint 4 vẫn hiển thị
đầy đủ phòng `active`, kể cả khi đã hết phòng trống cho khoảng ngày đó.

## 3. Hành vi khi query sai định dạng

Vì đây là route Blade (không phải API JSON), khi `FilterRoomRequest` validate
thất bại, Laravel mặc định **redirect back kèm session errors** (hành vi gốc
của `BaseFormRequest`, không override `failedValidation()`). View `/rooms`
cần hiển thị lỗi qua `$errors->any()` ngay tại form lọc, **không được vỡ
trang** hay rơi về danh sách rỗng không rõ lý do.

Ví dụ lỗi thường gặp:

| Tình huống | Lỗi hiển thị |
|---|---|
| `max_price` < `min_price` | "Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu." |
| `check_in` ở quá khứ | "Ngày nhận phòng không được ở quá khứ." |
| Có `check_in` nhưng thiếu `check_out` | "Vui lòng chọn ngày trả phòng." |
| `check_out` không sau `check_in` | "Ngày trả phòng phải sau ngày nhận phòng." |
| `amenities[]` chứa id không tồn tại | "Một hoặc nhiều tiện ích đã chọn không tồn tại." |

## 4. Dữ liệu trả về cho view (gợi ý cho BE2)

Controller chỉ nên truyền tối thiểu các biến sau cho view `rooms.index`:

- `roomTypes` — danh sách `RoomType` đã lọc + `status = active`, kèm `images`.
- `filters` — mảng các giá trị filter đã validate (dùng để giữ lại trong form
  và truyền tiếp sang `/customer/booking/create` khi khách bấm "Đặt phòng").

`FilterRoomRequest` cung cấp sẵn 3 helper để BE2 dùng trong service/controller:

- `keyword(): ?string` — từ khóa đã trim, `null` nếu rỗng.
- `amenityIds(): array` — danh sách id tiện ích đã chọn (mảng rỗng nếu không lọc).
- `hasDateRange(): bool` — `true` nếu có đủ cặp `check_in`/`check_out` hợp lệ.

## 5. Phạm vi KHÔNG thuộc tuần này

- Không lọc theo `hotel_id`/chi nhánh (chỉ 1 khách sạn).
- Không loại phòng theo availability thực tế (Sprint 5).
- Không phân trang (`per_page`) — chưa có trong yêu cầu tuần 7, bổ sung sau
  nếu seed demo đủ lớn để cần.
