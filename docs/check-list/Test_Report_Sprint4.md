# TEST REPORT — Sprint 4 (Tuần 8)
**Dự án:** Homi Hotel Booking
**Phạm vi:** Room List → Room Detail → Booking Form (đã liên kết với Booking flow của BE3)
**TV4/BE4 phụ trách:** [Tên thật]
**Tuần:** 8 (29/06 – 05/07/2026)
**Môi trường:** Local — Laravel 13.8, PHP 8.3.31, SQLite in-memory (test env)
**Branch/commit khi kiểm thử:** `main` @ `c8ac706` ("Room detail, form đặt phòng")
**Cách chạy:**
```bash
php artisan test tests/Feature/RoomType/RoomListTest.php
php artisan test tests/Feature/RoomType/RoomSearchListTest.php
php artisan test tests/Feature/RoomType/PublicRoomTypeSearchTest.php
php artisan test tests/Feature/RoomType/RoomDetailBookingFormTest.php
php artisan test tests/Feature/Booking/CustomerBookingFlowTest.php
php artisan test tests/Feature/Booking/AvailabilityApiTest.php
# hoặc gộp:
php artisan test --filter="RoomListTest|RoomSearchListTest|RoomDetailBookingFormTest|CustomerBookingFlowTest|RoomTypeDataTest|PublicRoomTypeSearchTest|AvailabilityApiTest"
```

---

## 1. Phạm vi kiểm thử tuần này

Theo kế hoạch Sprint 4 — Tuần 8, TV4/BE4 kiểm thử toàn bộ luồng khách hàng:

**Room List (`/rooms`) → Room Detail (`/rooms/{id}`) → Booking Form
(`/customer/bookings/create` → `POST /customer/bookings`)**, cộng với API
tương đương (`/api/v1/room-types`, `/api/v1/room-types/{id}`,
`/api/v1/room-types/{roomType}/availability`).

Trọng tâm theo đúng nhiệm vụ được giao:
1. Luồng liền mạch List → Detail → Booking Form (link, tham số ngày/quantity được giữ nguyên giữa các bước).
2. Dữ liệu thiếu — ảnh phòng, tiện ích khách sạn — không được làm vỡ trang.
3. Phòng không tồn tại hoặc không active (`hidden`, `maintenance`, đã soft-delete) — phải trả 404, không được rò rỉ dữ liệu hoặc crash.
4. Cập nhật API Documentation (`docs/check-list/TC_BE4_Tuan2_API_Contract_v1.md`).
5. Bug Report + Usability Checklist riêng (xem 2 file cùng thư mục).

**Không thuộc phạm vi tuần này** (đã làm ở tuần trước hoặc để tuần sau):
- CRUD dữ liệu room_types / validation giá-số lượng (Tuần 6, `RoomTypeDataTest.php`).
- Phân quyền admin/staff cho room-types (`AdminRoomTypeAccessTest.php`).
- API booking cho khách xem/hủy đơn của mình qua `/api/v1/bookings*` — các endpoint này **còn là stub** (`Chức năng đang phát triển (Tuần 11)`), chưa có gì để test thật; đã ghi nhận trong Bug Report mục quan sát.
- API admin quản lý booking (`/api/v1/admin/bookings*`) — kế hoạch Tuần 12.

---

## 2. Việc đã thực hiện tuần này

1. Đọc lại toàn bộ route/controller/service/view liên quan (routes/web.php, routes/api.php, `RoomController`, `Web\Customer\BookingController`, `Api\BookingController`, `PublicRoomTypeController`, `RoomTypeService`, `AvailabilityService`, `BookingService`, `DateRangeService`, views `rooms/index`, `rooms/show`, `customer/booking/create`, partials `_room-gallery`, `_amenities-list`).
2. Xác nhận bộ test có sẵn (128 test, viết ở tuần 6-7) **đã pass 100%** trước khi thêm gì mới.
3. Phát hiện 3 khoảng trống test thực sự (chưa ai viết) và bổ sung:
   - Trang Room Detail khi khách sạn **không có tiện ích nào** / **có tiện ích** — chưa có test nào assert hành vi này.
   - Trang Room Detail khi người dùng nhập **ngày không hợp lệ** (check_out trước check_in, check_in ở quá khứ) — route đã xử lý (`RoomController::show` bắt `ValidationException` và hiển thị `$availabilityError`) nhưng chưa có test xác nhận message hiển thị đúng.
   - API `GET /api/v1/room-types/{roomType}/availability` — có filter/logic đầy đủ trong `AvailabilityService` nhưng **chưa có file test riêng** cho chính API endpoint này (phòng không tồn tại/inactive, thiếu tham số, quantity vượt max, phản ánh đúng số phòng đã bị đặt).
4. Viết bổ sung **13 test case mới**, tất cả pass, không sửa logic nghiệp vụ nào:
   - `tests/Feature/RoomType/RoomDetailBookingFormTest.php` — thêm 4 test (tiện ích trống/có tiện ích, ngày không hợp lệ x2).
   - `tests/Feature/Booking/AvailabilityApiTest.php` — file mới, 8 test cho API kiểm tra phòng trống (bao gồm 1 test tái sử dụng cho case "phản ánh đúng số phòng đã bị đặt").
5. Cập nhật `docs/check-list/TC_BE4_Tuan2_API_Contract_v1.md`: bổ sung các endpoint room-types/hotel-info còn thiếu, sửa route availability cũ đã lỗi thời, đánh dấu rõ endpoint nào còn là stub, và ghi chú phát hiện quan trọng về format lỗi API thực tế khác tài liệu (xem Bug Report BUG-SPRINT4-01).

---

## 3. Tổng hợp kết quả chạy test (đã chạy thật, không phải điền mẫu)

| File test | Phạm vi | Tổng TC | Pass | Fail | Ghi chú |
|---|---|---|---|---|---|
| `RoomListTest.php` | Room list cơ bản (Tuần 7) | 7 | 7 | 0 | Regression — vẫn pass |
| `RoomSearchListTest.php` | Room list — filter/sort/perf (Tuần 7) | 30 | 30 | 0 | Regression — vẫn pass |
| `PublicRoomTypeSearchTest.php` | API `/api/v1/room-types` (Tuần 7) | 20 | 20 | 0 | Regression — vẫn pass |
| `RoomTypeDataTest.php` | Dữ liệu room_types (Tuần 6) | 42 | 42 | 0 | Regression — vẫn pass |
| `RoomDetailBookingFormTest.php` | **Room Detail + Booking Form (trọng tâm tuần 8)** | 25 | 25 | 0 | 21 test cũ + **4 test mới tuần này** |
| `CustomerBookingFlowTest.php` | Luồng đặt phòng end-to-end (web) | 8 | 8 | 0 | Có sẵn, xác nhận vẫn đúng |
| `AvailabilityApiTest.php` | **API kiểm tra phòng trống (mới tuần 8)** | 8 | 8 | 0 | File mới hoàn toàn |
| **Tổng** | | **140** | **140** | **0** | **339 assertions, ~2.3s** |

Lệnh xác nhận cuối cùng:
```
php artisan test --filter="RoomListTest|RoomSearchListTest|RoomDetailBookingFormTest|CustomerBookingFlowTest|RoomTypeDataTest|PublicRoomTypeSearchTest|AvailabilityApiTest"
→ {"tool":"pest","result":"passed","tests":140,"passed":140,"assertions":339,"duration_ms":2350}
```

---

## 4. Chi tiết test case theo từng yêu cầu của nhiệm vụ tuần 8

### 4.1 Luồng Room List → Room Detail → Booking Form

| TC | Kịch bản | File / method | Kết quả |
|---|---|---|---|
| RDF-01 | Danh sách phòng chứa link `/rooms/{id}` đúng | `test_room_list_contains_link_to_room_detail` | ✅ Pass |
| RDF-02 | Detail giữ nguyên `check_in`/`check_out`/`quantity` từ query string | `test_room_detail_check_in_check_out_params_are_preserved` | ✅ Pass |
| RDF-03 | Sau khi kiểm tra còn phòng trống, link "Đặt phòng ngay" trỏ đúng `customer/bookings/create` kèm tham số | `test_room_detail_booking_link_uses_correct_route` | ✅ Pass |
| RDF-04 | Booking form tự điền `room_type_id`, ngày, quantity từ query string | `test_booking_form_prefills_dates_from_query_params` | ✅ Pass |
| RDF-05 | Booking form tự điền tên/email khách từ user đăng nhập | `test_booking_form_prefills_customer_contact_info` | ✅ Pass |
| RDF-06 | Khách chưa đăng nhập bị redirect về login khi vào booking form | `test_guest_is_redirected_to_login_when_accessing_booking_form` | ✅ Pass |
| RDF-07 | Tạo đơn thành công → redirect đúng trang chi tiết đơn, tổng tiền đúng, trạng thái `pending`, payment `unpaid` | `test_customer_can_create_booking` | ✅ Pass |

### 4.2 Dữ liệu thiếu — ảnh & tiện ích

| TC | Kịch bản | File / method | Kết quả |
|---|---|---|---|
| MISS-01 | Phòng không có ảnh nào → hiện "Chưa có ảnh", không lỗi | `test_room_detail_without_images_does_not_crash` | ✅ Pass |
| MISS-02 | Phòng có nhiều ảnh → gallery render đủ | `test_room_detail_shows_all_room_images` | ✅ Pass |
| MISS-03 | Phòng không có mô tả → hiện placeholder "Chưa có mô tả chi tiết" | `test_room_detail_without_description_shows_placeholder` | ✅ Pass |
| MISS-04 **(mới)** | Khách sạn không có tiện ích nào → không hiện section "Tiện nghi khách sạn", trang vẫn OK | `test_room_detail_without_hotel_amenities_does_not_crash` | ✅ Pass |
| MISS-05 **(mới)** | Khách sạn có tiện ích → hiện đúng tên tiện ích (badge) | `test_room_detail_shows_hotel_amenities_when_present` | ✅ Pass |

### 4.3 Phòng không tồn tại hoặc inactive

| TC | Kịch bản | File / method | Kết quả |
|---|---|---|---|
| INV-01 | Phòng `hidden` → 404 (web) | `test_hidden_room_detail_returns_404` | ✅ Pass |
| INV-02 | Phòng `maintenance` → 404 (web) | `test_maintenance_room_detail_returns_404` | ✅ Pass |
| INV-03 | Phòng đã soft-delete → 404 (web) | `test_soft_deleted_room_detail_returns_404` | ✅ Pass |
| INV-04 | ID phòng không tồn tại → 404 (web) | `test_nonexistent_room_detail_returns_404` | ✅ Pass |
| INV-05 | Booking form với `room_type_id` của phòng hidden → 404 | `test_booking_form_with_inactive_room_type_returns_404` | ✅ Pass |
| INV-06 | Phòng `hidden` → 404 (API `GET /api/v1/room-types/{id}`) | `test_TC_PRS_071_hidden_room_returns_404_to_public` | ✅ Pass |
| INV-07 | ID không tồn tại → 404 (API) | `test_TC_PRS_072_nonexistent_room_returns_404` | ✅ Pass |
| INV-08 **(mới)** | Availability API với ID không tồn tại → 404 | `test_availability_check_for_nonexistent_room_returns_404` | ✅ Pass |
| INV-09 **(mới)** | Availability API với phòng `hidden` → 404 | `test_availability_check_for_hidden_room_returns_404` | ✅ Pass |
| INV-10 **(mới)** | Availability API với phòng `maintenance` → 404 | `test_availability_check_for_maintenance_room_returns_404` | ✅ Pass |

### 4.4 Ngày không hợp lệ trên Room Detail (mới phát hiện & bổ sung)

| TC | Kịch bản | File / method | Kết quả |
|---|---|---|---|
| DATE-01 **(mới)** | `check_out` trước `check_in` → hiện message lỗi tiếng Việt đúng, trang vẫn 200 (không crash) | `test_room_detail_shows_error_when_check_out_before_check_in` | ✅ Pass |
| DATE-02 **(mới)** | `check_in` ở quá khứ → hiện message lỗi tiếng Việt đúng | `test_room_detail_shows_error_when_check_in_in_the_past` | ✅ Pass |

### 4.5 API kiểm tra phòng trống — bổ sung toàn bộ (file mới `AvailabilityApiTest.php`)

| TC | Kịch bản | Kết quả |
|---|---|---|
| AVL-01 | Phòng active, đủ ngày hợp lệ → trả `can_book: true`, `available_quantity` đúng | ✅ Pass |
| AVL-02 | Thiếu `check_in` → 422 | ✅ Pass |
| AVL-03 | Thiếu `check_out` → 422 | ✅ Pass |
| AVL-04 | `quantity` > 10 → 422 | ✅ Pass |
| AVL-05 | Đã có booking chiếm hết phòng → `available_quantity: 0`, `can_book: false` | ✅ Pass |
| AVL-06, AVL-07, AVL-08 | Xem mục 4.3 (INV-08/09/10) | ✅ Pass |

---

## 5. Lỗi / quan sát phát hiện

Xem chi tiết đầy đủ tại `Bug_Report_Sprint4.md`. Tóm tắt:

| Mức độ | Số lượng | Ghi chú |
|---|---|---|
| 🔴 Critical | 0 | — |
| 🟠 High | 0 | — |
| 🟡 Medium | 1 | Format lỗi API (422/404) không khớp tài liệu `api-error-catalog.md` |
| 🟢 Low / Quan sát | 2 | Debug stack trace lộ khi APP_DEBUG=true; API booking khách hàng còn stub |

**Không phát hiện lỗi nghiệp vụ nào trong luồng Room List → Detail → Booking
Form** — toàn bộ 140 test đều pass, bao gồm 13 test mới viết riêng cho các
case biên (thiếu ảnh/tiện ích, phòng không tồn tại/inactive, ngày không hợp
lệ). Các vấn đề tìm thấy đều thuộc nhóm tài liệu/observation, không phải bug
chức năng nghiêm trọng.

---

## 6. Checklist nghiệm thu Sprint 4 — Room List/Detail/Booking Form

| # | Điều kiện | Trạng thái |
|---|-----------|------------|
| 1 | Luồng List → Detail → Booking Form hoạt động liền mạch, giữ đúng tham số ngày/quantity | ✅ |
| 2 | Phòng thiếu ảnh/tiện ích/mô tả không làm vỡ trang, hiện placeholder hợp lý | ✅ |
| 3 | Phòng `hidden`/`maintenance`/đã xóa/không tồn tại → 404 nhất quán ở cả Web và API | ✅ |
| 4 | Ngày không hợp lệ (check_out < check_in, check_in quá khứ) → hiện lỗi rõ ràng, không crash | ✅ |
| 5 | API kiểm tra phòng trống hoạt động đúng, phản ánh đúng số phòng đã đặt | ✅ |
| 6 | Booking form kiểm soát truy cập đúng (redirect login nếu chưa đăng nhập) | ✅ |
| 7 | Tài liệu API đã cập nhật đúng thực tế (route, trạng thái hoàn thiện) | ✅ |
| 8 | Không còn lỗi Critical/High sau kiểm thử | ✅ (0 lỗi Critical/High) |

**Kết luận:** Đủ điều kiện nghiệm thu module Room List/Detail/Booking Form
(Sprint 4 — Tuần 8). API booking dành cho khách xem/hủy đơn (`/api/v1/bookings*`)
và toàn bộ API quản trị booking vẫn là việc của Tuần 11-12, không chặn nghiệm
thu tuần này.

---

## 7. Việc cần làm tiếp

1. Báo BE1 xác nhận hướng xử lý mismatch format lỗi API (BUG-SPRINT4-01) — implement handler tập trung hay sửa lại tài liệu.
2. Nhắc BE3 khi làm Tuần 11 (`/api/v1/bookings` GET/show/cancel): tái sử dụng test case pattern trong `CustomerBookingFlowTest.php` (đã có sẵn cho web) để viết test API tương ứng.
3. Kiểm tra `APP_DEBUG` phải là `false` trước khi deploy production (tránh lộ stack trace ở response lỗi).
