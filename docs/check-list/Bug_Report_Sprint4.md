# BUG REPORT — Sprint 4 (Tuần 8)
**Dự án:** Homi Hotel Booking
**Module:** Room List → Room Detail → Booking Form
**TV4/BE4 phụ trách:** [Tên thật]
**Tuần kiểm thử:** Tuần 8 (29/06 – 05/07/2026)
**Ngày tạo:** 2026-07-01
**Branch/commit:** `main` @ `c8ac706`
**Trạng thái tổng quan:** ✅ Không có lỗi Critical/High trong luồng chức năng chính. 1 lỗi Medium (tài liệu API sai lệch thực tế), 2 quan sát Low.

---

## Quy ước mức độ lỗi

| Mức | Định nghĩa |
|-----|------------|
| 🔴 Critical | Sai dữ liệu lưu DB, mất dữ liệu, lỗi bảo mật, chặn merge. |
| 🟠 High | Logic nghiệp vụ sai, chặn luồng chính, cần sửa trong tuần. |
| 🟡 Medium | Validation/tài liệu/message không đúng chuẩn, không chặn nghiệm thu nhưng cần sửa trước khi phía khác (frontend/mobile) dựa vào. |
| 🟢 Low | Quan sát/cải thiện nhỏ, không ảnh hưởng nghiệp vụ hiện tại. |

---

## Cách tái hiện chung

```bash
php artisan test --filter="RoomListTest|RoomSearchListTest|RoomDetailBookingFormTest|CustomerBookingFlowTest|RoomTypeDataTest|PublicRoomTypeSearchTest|AvailabilityApiTest"
```
Toàn bộ 140 test case (bao gồm 13 test mới của tuần này) **PASS 100%** —
không phát hiện lỗi nghiệp vụ nào trong luồng Room List/Detail/Booking Form.
Các mục dưới đây là lỗi/quan sát phát hiện được khi đọc code + gọi thử API
trực tiếp (không phải từ một test case bị fail).

---

## BUG-SPRINT4-01 — Format lỗi API thực tế khác với tài liệu `api-error-catalog.md`

| Trường | Nội dung |
|--------|----------|
| **Mã lỗi** | BUG-SPRINT4-01 |
| **Mức độ** | 🟡 Medium |
| **Chức năng** | Toàn bộ API `/api/v1/*` khi trả lỗi 404/422 (bao gồm `/api/v1/room-types/{id}`, `/api/v1/room-types/{roomType}/availability` đang test tuần này) |
| **Tiêu đề** | `docs/api-error-catalog.md` mô tả envelope lỗi chuẩn `{success, message, error_code, errors}` do exception handler tập trung xử lý, nhưng `bootstrap/app.php` thực tế có `->withExceptions(function (Exceptions $exceptions): void { // trống })` — không xử lý gì cả. |
| **Bước tái hiện** | 1. `GET /api/v1/room-types?capacity=0` (lỗi validation). <br>2. `GET /api/v1/room-types/999999` (không tồn tại). |
| **Kết quả thực tế** | 422: `{"message":"Sức chứa tối thiểu là 1 khách.","errors":{"capacity":["..."]}}` — không có `success`, không có `error_code: VALIDATION_ERROR`. <br>404: response mặc định của Laravel (`{"message": "No query results for model ..."}`), không có `success`/`error_code: NOT_FOUND`. Khi `APP_DEBUG=true` (env test/local mặc định) response 404 còn kèm **toàn bộ stack trace và đường dẫn file server** trong JSON. |
| **Kết quả kỳ vọng (theo tài liệu)** | `{"success": false, "message": "...", "error_code": "VALIDATION_ERROR", "errors": {...}}` cho 422; `{"success": false, "message": "...", "error_code": "NOT_FOUND"}` cho 404. |
| **Root cause** | `App\Enums\ErrorCode` được tài liệu nhắc tới **không tồn tại trong code** (`grep -r "ErrorCode" app/` chỉ tìm thấy chuỗi `'error_code' => 'ACCOUNT_LOCKED'` viết tay trong `RoleMiddleware.php`, không có enum, không có handler tập trung). Tài liệu có vẻ được viết theo kế hoạch (aspirational) chứ chưa khớp code đã merge. |
| **Ảnh hưởng** | Không chặn luồng Room List/Detail/Booking Form hiện tại (đều dùng Blade + session, không phụ thuộc `error_code`). Nhưng nếu FE/mobile (khi phát triển sau) dựa vào tài liệu để bắt `error_code`, sẽ luôn nhận `undefined` — có thể gây lỗi khi hiển thị thông báo lỗi cho người dùng. Debug stack trace lộ trong 404 là rủi ro bảo mật thông tin nếu vô tình để `APP_DEBUG=true` ở production. |
| **Người sửa (đề xuất)** | BE1 (chủ sở hữu `api-error-catalog.md` và chuẩn response chung) — quyết định: implement `withExceptions()` đúng như tài liệu, hoặc sửa tài liệu cho khớp thực tế hiện tại (đơn giản hơn, ít rủi ro hơn nếu chuẩn `{success, message, errors}` không có `error_code` đã đủ dùng). |
| **Trạng thái** | 🟡 Open — đã ghi chú tại `TC_BE4_Tuan2_API_Contract_v1.md` |
| **Test liên quan** | Không có test nào assert `error_code` hiện tại (đúng với thực tế) — nếu BE1 quyết định implement theo tài liệu, cần thêm test assert `error_code` cho các route trong scope Sprint 4 (`/api/v1/room-types*`, `/api/v1/room-types/{id}/availability`). |

---

## Quan sát 1 (Low) — Debug stack trace lộ trong response lỗi khi `APP_DEBUG=true`

| Trường | Nội dung |
|---|---|
| **Mức độ** | 🟢 Low (nhưng cần Critical nếu vô tình bật ở production) |
| **Mô tả** | Xem chi tiết trong BUG-SPRINT4-01 — 404 response khi debug bật kèm full path server (`C:\laragon\www\DATN\homi-hotel-booking\vendor\...`) và trace. Đây là hành vi mặc định của Laravel khi `APP_DEBUG=true`, không phải lỗi riêng của module Room/Booking. |
| **Khuyến nghị** | Xác nhận `.env` production có `APP_DEBUG=false`. Không cần sửa code cho Sprint 4 — chỉ là nhắc nhở vận hành (đã ghi vào mục "Việc cần làm tiếp" của Test Report). |
| **Trạng thái** | 🟢 Ghi nhận, không mở bug riêng |

## Quan sát 2 (Low) — API booking cho khách hàng (`/api/v1/bookings*`) vẫn là stub

| Trường | Nội dung |
|---|---|
| **Mức độ** | 🟢 Low (đã biết trước, đúng kế hoạch, không phải bug) |
| **Mô tả** | `App\Http\Controllers\Api\BookingController::myBookings()`, `show()`, `cancel()` trả `success(...,'Chức năng đang phát triển (Tuần 11)')` thay vì dữ liệu thật. `POST /api/v1/bookings` (tạo đơn) đã hoàn thiện đầy đủ và có test. Toàn bộ `/api/v1/admin/bookings*` cũng là stub (Tuần 12). |
| **Ảnh hưởng đến Sprint 4** | Không — nhiệm vụ tuần 8 là Web flow (`/customer/bookings/*`), đã hoàn thiện 100% và có test đầy đủ. Ghi nhận để BE3 tuần 11-12 không quên. |
| **Trạng thái** | 🟢 Ghi nhận, theo dõi (không phải bug của tuần này) |

---

## Bảng tổng hợp

| Bug ID | Mức | Tiêu đề | TC liên quan | Trạng thái |
|--------|-----|---------|---------------|------------|
| BUG-SPRINT4-01 | 🟡 Medium | Format lỗi API 422/404 không khớp tài liệu error catalog | — (phát hiện qua gọi API trực tiếp) | Open |

| Mức độ | Tổng | Open | Fixed | Closed |
|--------|------|------|-------|--------|
| 🔴 Critical | 0 | 0 | 0 | 0 |
| 🟠 High | 0 | 0 | 0 | 0 |
| 🟡 Medium | 1 | 1 | 0 | 0 |
| 🟢 Low | 2 (quan sát) | 2 | 0 | 0 |

> ⚠️ Người phát hiện lỗi (TV4/BE4) không tự đóng lỗi. Sau khi BE1 xác nhận
> hướng xử lý BUG-SPRINT4-01 và sửa (nếu cần), TV4/BE4 chạy lại test liên quan
> rồi mới cập nhật trạng thái Closed.
