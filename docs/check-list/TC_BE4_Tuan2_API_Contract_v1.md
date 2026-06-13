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
| GET | /api/v1/hotels/{hotel}/availability | Không | - | Kiểm tra phòng trống (skeleton, hoàn thiện Tuần 9) | BE3 |
| GET | /api/v1/bookings | Có | customer | Danh sách đơn của tôi (skeleton, hoàn thiện Tuần 11) | BE3 |
| GET | /api/v1/bookings/{booking} | Có | customer | Chi tiết đơn (skeleton, hoàn thiện Tuần 11) | BE3 |
| POST | /api/v1/bookings | Có | customer | Tạo đơn (skeleton, hoàn thiện Tuần 10) | BE3 |
| POST | /api/v1/bookings/{booking}/cancel | Có | customer | Hủy đơn (skeleton, hoàn thiện Tuần 11) | BE3 |
| GET | /api/v1/admin/bookings | Có | admin, staff | Danh sách đơn (skeleton, hoàn thiện Tuần 12) | BE3 |
| GET | /api/v1/admin/bookings/{booking} | Có | admin, staff | Chi tiết đơn (skeleton, hoàn thiện Tuần 12) | BE3 |
| PUT | /api/v1/admin/bookings/{booking}/status | Có | admin, staff | Cập nhật trạng thái đơn (skeleton, hoàn thiện Tuần 12) | BE3 |
| PUT | /api/v1/admin/bookings/{booking}/payment | Có | admin, staff | Cập nhật trạng thái thanh toán (skeleton, hoàn thiện Tuần 12) | BE3 |

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

## Ghi chú
- Tất cả endpoint có auth dùng header: `Authorization: Bearer {token}` (Laravel Sanctum).
- Token lấy từ response `data.token` khi gọi `/api/v1/auth/login`.
- Bảng này sẽ được mở rộng tiếp ở các tuần 5-13 khi BE2/BE3 thêm API hotels, rooms, search, booking...