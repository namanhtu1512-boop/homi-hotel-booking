# API CONTRACT v1 - HOMI BACKEND
Tổng hợp từ route list (Tuần 1-3). Cập nhật mỗi khi có route mới.

## Chuẩn response chung
**Thành công:**
```json
{
  "success": true,
  "message": "Thông báo",
  "data": { }
}
```

**Có phân trang:**
```json
{
  "success": true,
  "message": "Thành công",
  "data": [ ],
  "meta": { "current_page": 1, "per_page": 15, "total": 100, "last_page": 7 }
}
```

**Lỗi:**
```json
{
  "success": false,
  "message": "Thông báo lỗi",
  "errors": { "field": ["Chi tiết lỗi"] }
}
```

## Danh sách endpoint

| Method | Endpoint | Auth | Role | Mô tả | Phụ trách |
|---|---|---|---|---|---|
| GET | /api/health | Không | - | Health check server + DB | BE4 |
| POST | /api/v1/register | Không | - | Đăng ký customer | BE1 |
| POST | /api/v1/login | Không | - | Đăng nhập, trả token | BE1 |
| POST | /api/v1/logout | Có | any | Đăng xuất thiết bị hiện tại | BE1 |
| GET | /api/v1/me | Có | any | Xem profile hiện tại | BE1 |
| PUT | /api/v1/profile | Có | any | Cập nhật tên/sđt/địa chỉ (email tùy chọn) | BE1 |
| PUT | /api/v1/change-password | Có | any | Đổi mật khẩu, thu hồi toàn bộ token | BE1 |
| GET | /api/v1/admin/users | Có | admin, staff | Danh sách user, filter role/search | BE2 |
| GET | /api/v1/admin/users/{id} | Có | admin, staff | Chi tiết user | BE2 |
| PATCH | /api/v1/admin/users/{id}/toggle-status | Có | admin | Khóa/mở khóa user | BE2 |
| GET | /api/v1/admin/audit-logs | Có | admin | Danh sách audit log | BE2 |
| GET | /api/v1/hotel-info | Không | - | Xem thông tin khách sạn (singleton) | BE2 |
| GET | /api/v1/admin/hotel-info | Có | admin, staff | Xem thông tin khách sạn (admin) | BE2 |
| PUT | /api/v1/admin/hotel-info | Có | admin, staff | Cập nhật thông tin khách sạn | BE2 |
| PATCH | /api/v1/admin/hotel-info/toggle-maintenance | Có | admin, staff | Bật/tắt chế độ bảo trì khách sạn | BE2 |
| DELETE | /api/v1/admin/hotel-info/images/{imageId} | Có | admin, staff | Xóa 1 ảnh khách sạn | BE2 |
| GET | /api/v1/room-types | Không | - | **[Cập nhật Tuần 7-8]** Danh sách phòng active, filter keyword/price/capacity, phân trang | BE2/BE4 |
| GET | /api/v1/room-types/{id} | Không | - | **[Cập nhật Tuần 7-8]** Chi tiết 1 phòng active (kèm ảnh). 404 nếu hidden/maintenance/soft-deleted/không tồn tại | BE2/BE4 |
| GET | /api/v1/room-types/{roomType}/availability | Không | - | **[Đã đổi route so với bản v1 — không còn `/hotels/{hotel}/...` vì hệ thống chỉ có 1 khách sạn]** Kiểm tra phòng trống theo ngày, đã hoàn thiện & có test (`AvailabilityApiTest`) | BE3/BE4 |
| GET | /api/v1/admin/room-types | Có | admin, staff | Danh sách phòng (mọi trạng thái, kể cả hidden/maintenance) | BE2 |
| POST | /api/v1/admin/room-types | Có | admin, staff | Tạo loại phòng mới | BE2 |
| GET | /api/v1/admin/room-types/{id} | Có | admin, staff | Chi tiết loại phòng (admin) | BE2 |
| PUT | /api/v1/admin/room-types/{id} | Có | admin, staff | Cập nhật loại phòng | BE2 |
| DELETE | /api/v1/admin/room-types/{id} | Có | admin, staff | Xóa loại phòng (soft-delete, hoặc chuyển `hidden` nếu có booking active) | BE2 |
| POST | /api/v1/admin/room-types/{id}/restore | Có | admin, staff | Khôi phục loại phòng đã xóa | BE2 |
| PATCH | /api/v1/admin/room-types/{id}/price | Có | admin, staff | Cập nhật riêng giá phòng | BE2 |
| PATCH | /api/v1/admin/room-types/{id}/inventory | Có | admin, staff | Cập nhật riêng số lượng phòng | BE2 |
| DELETE | /api/v1/admin/room-types/{roomTypeId}/images/{imageId} | Có | admin, staff | Xóa 1 ảnh loại phòng | BE2 |
| GET | /api/v1/bookings | Có | customer | Danh sách đơn của tôi — **⚠️ CHƯA triển khai** (trả `[]` cứng, xem Bug Report Sprint 4) | BE3 |
| GET | /api/v1/bookings/{booking} | Có | customer | Chi tiết đơn — **⚠️ CHƯA triển khai** (trả `[]` cứng) | BE3 |
| POST | /api/v1/bookings | Có | customer | Tạo đơn — **đã hoàn thiện**, có test (`CustomerBookingFlowTest`, web tương đương đã pass) | BE3 |
| POST | /api/v1/bookings/{booking}/cancel | Có | customer | Hủy đơn — **⚠️ CHƯA triển khai** (trả `[]` cứng) | BE3 |
| GET | /api/v1/admin/bookings | Có | admin, staff | Danh sách đơn — **⚠️ CHƯA triển khai** (kế hoạch Tuần 12) | BE3 |
| GET | /api/v1/admin/bookings/{booking} | Có | admin, staff | Chi tiết đơn — **⚠️ CHƯA triển khai** (kế hoạch Tuần 12) | BE3 |
| PUT | /api/v1/admin/bookings/{booking}/status | Có | admin, staff | Cập nhật trạng thái đơn — **⚠️ CHƯA triển khai** (kế hoạch Tuần 12) | BE3 |
| PUT | /api/v1/admin/bookings/{booking}/payment | Có | admin, staff | Cập nhật trạng thái thanh toán — **⚠️ CHƯA triển khai** (kế hoạch Tuần 12) | BE3 |

> **Ghi chú Web routes tương đương (không phải `/api/v1`, dùng session/Blade):**
> `GET/POST /customer/bookings*` (index/create/store/show/cancel) đã hoàn thiện đầy đủ và có test —
> xem `CustomerBookingFlowTest.php`, `RoomDetailBookingFormTest.php`.
> `GET /rooms`, `GET /rooms/{id}` (danh sách & chi tiết phòng public) cũng đã hoàn thiện.

## Ghi chú phân quyền danh sách user

`GET /api/v1/admin/users` và `GET /api/v1/admin/users/{id}` cho phép cả
**admin và staff** (route group `role:admin,staff`). Chỉ riêng hành động
**khóa/mở khóa** (`toggle-status`) yêu cầu **admin**. Postman collection và
test case RBAC đã được cập nhật theo đúng quy tắc này ở Tuần 3.

## Mã lỗi chuẩn (error catalog rút gọn)

| HTTP Code | Trường hợp |
|---|---|
| 401 | Chưa đăng nhập / token hết hạn hoặc đã bị huỷ |
| 403 | Đã đăng nhập nhưng không đủ quyền, hoặc tài khoản bị khóa |
| 404 | Resource không tồn tại |
| 422 | Lỗi validation đầu vào |
| 500 | Lỗi server không xác định (cần báo Critical ngay) |

## ⚠️ Cập nhật Tuần 8 (BE4) — Format lỗi thực tế KHÁC với tài liệu

`docs/api-error-catalog.md` mô tả envelope lỗi chuẩn có `success`, `message`,
`error_code`, `errors`, và nói `bootstrap/app.php` xử lý tập trung
`ModelNotFoundException`/`ValidationException` để bọc theo format đó.

Sau khi gọi trực tiếp API và đọc `bootstrap/app.php` (`->withExceptions(function
(Exceptions $exceptions): void { // trống })`), thực tế:

- **422 (validation)** trả về mặc định của Laravel: `{"message": "...", "errors": {...}}`
  — **không có** `success`, **không có** `error_code`.
- **404 (ModelNotFoundException)** trả về mặc định của Laravel — không có
  `success`/`error_code`; khi `APP_DEBUG=true` còn kèm cả stack trace đầy đủ
  (đường dẫn file server) trong response JSON.
- Chỉ có `RoleMiddleware` là tự tay trả `error_code: ACCOUNT_LOCKED` cho tài
  khoản bị khóa — đây là trường hợp DUY NHẤT có `error_code` thật sự tồn tại
  trong code. `App\Enums\ErrorCode` được nhắc tới trong error catalog **không
  tồn tại trong code**.

Chi tiết xem `Bug_Report_Sprint4.md` (BUG-SPRINT4-01). Đã báo cho BE1 (chủ sở
hữu `api-error-catalog.md` và chuẩn response) để xác nhận hướng xử lý: hoặc
implement handler tập trung như tài liệu mô tả, hoặc sửa lại tài liệu cho khớp
thực tế.

## Ghi chú
- Tất cả endpoint có auth dùng header: `Authorization: Bearer {token}` (Laravel Sanctum).
- Token lấy từ response `data.token` khi gọi `/api/v1/auth/login`.
- Bảng này sẽ được mở rộng tiếp ở các tuần 5-13 khi BE2/BE3 thêm API hotels, rooms, search, booking...
- **Cập nhật Tuần 7-8:** đã bổ sung room-types (public + admin), hotel-info, audit-logs,
  availability; đã sửa route availability cũ (`/hotels/{hotel}/availability`) thành route
  thật đang chạy (`/room-types/{roomType}/availability`).