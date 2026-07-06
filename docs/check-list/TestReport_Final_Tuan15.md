# Test Report Tổng Hợp — Tuần 15 (Sprint 8, chuẩn bị nộp)

**Dự án:** Homi Hotel Booking — Laravel 13 Blade monolith, 1 khách sạn duy nhất.
**Ngày tổng hợp:** 2026-07-06
**Lệnh chạy:** `php artisan test`
**Kết quả:** ✅ **538/538 test pass**, 1276 assertion, 0 failed, 0 skipped.

---

## 1. Breakdown theo module

| Nhóm | Số test | Nội dung chính |
|---|---|---|
| `tests/Feature/RoomType/` | 157 | Public list/search/filter, admin/staff CRUD, giá, tồn kho, phân quyền |
| `tests/Feature/Booking/` | 94 | Availability/overlap, tạo đơn (E2E), customer quản lý đơn, admin xác nhận/hủy/thanh toán |
| `tests/Feature/Admin/` | 88 | Dashboard, khách hàng, banner, tin tức, liên hệ, database viewer |
| `tests/Feature/Auth/` | 32 | Đăng ký, đăng nhập (customer/admin), RBAC, rate-limit |
| `tests/Feature/HotelInfo/` | 31 | Thông tin khách sạn (web + API), phân quyền |
| `tests/Feature/Api/` | 30 | API JSON `/api/v1/*` — booking, auth (register/login/me/logout) |
| `tests/Unit/Policies/` | 42 | BookingPolicy, RoomTypePolicy, HotelInfoPolicy |
| `tests/Unit/Services/` | 29 | AvailabilityService, PricingService, RoomInventoryService |
| `tests/Feature/AuditLog/` | 10 | Ghi log thao tác admin |
| `tests/Feature/Review/` | 10 | Đánh giá — điều kiện, chống trùng, admin duyệt |
| `tests/Feature/Wishlist/` | 9 | Yêu thích phòng |
| `tests/Unit/Models/` | 4 | `Promotion::discountFor()` |
| `tests/Feature/HomeTest.php` | 2 | Trang chủ |
| **Tổng** | **538** | |

## 2. Lịch sử review — bug đã tìm và sửa (tham chiếu chi tiết ở các báo cáo trước)

| Đợt | Tài liệu | Tóm tắt |
|---|---|---|
| Tuần 14 | [`Bug_Report_Sprint7_Tuan14.md`](Bug_Report_Sprint7_Tuan14.md) | 18 bug (3 Critical, 3 High, 9 Medium, 3 Low) — race condition overbook, lộ password hash ở `/admin/database`, seed demo sai giá/mô tả, `Promotion::discountFor()` sai khi percent=0, thiếu audit log, storage leak... |
| Tuần 15 (đợt này) | tài liệu này | Vá rate-limit login/register/contact, thêm SEO meta, hoàn thiện 7 method API booking còn stub, phát hiện + sửa `AuthController::logout()` crash 500 khi xác thực qua session thay vì Bearer token thật, lấp toàn bộ lỗ hổng test cho News và API Auth (trước đó 0%) |

**Bug mới tìm thấy trong lúc viết test tuần này:**

- `App\Http\Controllers\Api\AuthController::logout()` gọi thẳng
  `currentAccessToken()->delete()` — nếu request được xác thực qua session
  guard thay vì Bearer token thật (Sanctum hỗ trợ song song 2 kiểu), Sanctum
  trả về `TransientToken` (không có `delete()`) → vỡ thành lỗi 500. Đã sửa
  bằng cách kiểm tra `instanceof PersonalAccessToken` trước khi xóa. Test
  chặn regression: `tests/Feature/Api/AuthApiTest.php::test_logout_revokes_current_token`.

## 3. Phạm vi được test — không còn "mảng tối"

Trước đợt review tuần 14-15, 2 mảng sau **hoàn toàn chưa có test**:
News (`tests/Feature/Admin/NewsManagementTest.php`, 11 case) và toàn bộ
`Api\BookingController`/`Api\AuthController` (`tests/Feature/Api/`, 30 case
— trước đó 7/9 method booking còn là stub, giờ đã hoàn thiện khớp Blade và
có test đầy đủ). Sau đợt này, mọi controller trong `app/Http/Controllers/`
đều có ít nhất 1 file test tương ứng.

## 4. Giải thích nghiệp vụ: chống đặt trùng phòng (overlap + transaction)

Yêu cầu nghiệp vụ lõi quan trọng nhất của dự án: **không được để 2 khách đặt
vượt quá số phòng thực có** trong cùng khoảng ngày. Cơ chế gồm 2 lớp:

### Lớp 1 — Tính đúng số phòng đã bị giữ (overlap theo ngày)

`AvailabilityService::getBookedQuantity()` (`app/Services/AvailabilityService.php`)
tính tổng `quantity` của mọi `booking_items` thuộc booking đang ở trạng thái
"giữ phòng" (`pending`, `confirmed`, `checked_in` — xem
`BookingStatus::holdingStatuses()`) mà khoảng ngày **giao nhau** với khoảng
khách đang hỏi:

```
booking.check_in < $checkOut  AND  booking.check_out > $checkIn
```

Điều kiện này đúng cho cả 4 kiểu giao nhau (trùng hoàn toàn, giao đầu, giao
cuối, nằm trong/bao ngoài) — không cần liệt kê từng case riêng vì bản chất
toán học của phép so sánh khoảng đã bao quát đủ. Booking đã `cancelled`
không nằm trong `holdingStatuses()` nên tự động không tính vào số đã giữ —
đây là lý do hủy đơn xong availability "tính lại đúng ngay" mà không cần
cache hay cron dọn dẹp gì cả: mọi lần gọi `check()` đều là 1 query SELECT
trực tiếp trên dữ liệu mới nhất.

### Lớp 2 — Chống race condition khi 2 request chạy song song

Chỉ tính đúng số liệu là chưa đủ: nếu 2 khách cùng bấm "Đặt phòng" cho
phòng cuối cùng trong cùng 1 khoảnh khắc, cả 2 request có thể cùng đọc thấy
"còn 1 phòng" trước khi request nào kịp ghi dữ liệu — dẫn tới overbook dù
logic overlap ở Lớp 1 hoàn toàn đúng. `BookingService::create()`
(`app/Services/BookingService.php`) xử lý bằng 2 bước trong 1
`DB::transaction()`:

1. **Khóa row** các `RoomType` liên quan bằng `lockForUpdate()`, theo thứ tự
   `id` tăng dần (tránh deadlock khi 2 đơn khác nhau khóa nhiều loại phòng
   chung theo thứ tự ngược nhau).
2. **Tính lại** `AvailabilityService::canBook()` ngay sau khi có khóa, rồi
   mới `INSERT` booking/booking_items.

`SELECT ... FOR UPDATE` trong MySQL luôn đọc **dữ liệu mới nhất đã commit**
bất kể mức cô lập giao dịch (isolation level) — nên nếu request A đang giữ
khóa, request B phải đợi A commit xong mới được đọc, và lúc đó B sẽ thấy
đúng số phòng đã bị A giữ, không còn đọc được số liệu "cũ" nữa. Đây là lý do
tại sao bước tính lại availability phải nằm **sau** `lockForUpdate()` và
**trong cùng transaction** với `INSERT` — nếu tách rời, khoảng hở giữa lúc
check và lúc insert vẫn có thể bị chen ngang.

Giới hạn đã biết: race condition thật cần 2 tiến trình PHP chạy song song để
tái hiện — SQLite (dùng khi chạy `php artisan test`) không hỗ trợ khóa
row-level thật sự như MySQL, nên cơ chế này chỉ phát huy đúng trên MySQL
production/staging, không thể viết unit test tự động xác nhận trực tiếp
hành vi khóa trong CI hiện tại. Điều có thể và đã được test: toàn bộ luồng
tạo đơn tuần tự (không vượt số lượng khi hết phòng) — `BookingE2ETest.php`,
`CustomerBookingFlowTest.php`.
