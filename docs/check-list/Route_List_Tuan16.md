# Danh Sách Route Chính — Tuần 16 (đóng gói bản nộp)

Xuất từ `php artisan route:list --except-vendor` (140 route), tổ chức lại
theo khu vực để dễ đối chiếu khi nghiệm thu. Route chi tiết đầy đủ luôn có
thể tái tạo bằng chính lệnh trên — file này chỉ là bản tóm tắt các route
nghiệp vụ chính, không liệt kê route phụ trợ (đăng nhập/đăng xuất...) trùng
lặp giữa các bảng.

## Public (không cần đăng nhập)

| Method | URI | Chức năng |
|---|---|---|
| GET | `/` | Trang chủ |
| GET | `/about` | Giới thiệu khách sạn |
| GET | `/rooms` | Danh sách phòng, lọc theo giá/sức chứa/tiện ích |
| GET | `/rooms/{id}` | Chi tiết phòng, kiểm tra trống |
| GET | `/promotions` | Khuyến mãi đang áp dụng |
| GET | `/news`, `/news/{slug}` | Tin tức |
| GET/POST | `/contact` | Liên hệ (có rate-limit 5 lần/phút) |
| GET | `/health` | Health check |

## Customer (`auth` + `role:customer`)

| Method | URI | Chức năng |
|---|---|---|
| GET/POST | `/customer/register`, `/customer/login` | Đăng ký/đăng nhập (rate-limit 5 lần/phút) |
| GET | `/customer/dashboard` | Tổng quan tài khoản |
| GET/POST | `/customer/profile`, `/profile/email`, `/profile/password` | Hồ sơ, đổi email/mật khẩu |
| GET/POST | `/customer/bookings`, `/bookings/create`, `/bookings/{id}` | Đặt phòng, xem đơn |
| POST | `/customer/bookings/{id}/cancel` | Hủy đơn |
| POST | `/customer/bookings/{id}/pay/{online,bank-transfer,deposit}` | Tự thanh toán (mô phỏng) |
| GET/POST/PATCH/DELETE | `/customer/wishlist*` | Yêu thích phòng |
| GET/POST | `/customer/reviews/create`, `/reviews` | Viết đánh giá |

## Admin (`role:admin`)

| Method | URI | Chức năng |
|---|---|---|
| GET | `/admin/dashboard` | Thống kê tổng quan (đơn, doanh thu, tỷ lệ hủy, lấp đầy) |
| GET | `/admin/database` | Xem nhanh dữ liệu thô (đã vá lỗ hổng lộ password — xem Bug_Report_Sprint7_Tuan14.md) |
| GET/PUT/PATCH | `/admin/hotel-info*` | Thông tin khách sạn singleton |
| CRUD | `/admin/room-types*` | Loại phòng, giá, tồn kho, soft-delete |
| GET/POST/PATCH | `/admin/bookings*` | Xác nhận/hủy/hoàn thành đơn |
| GET/PATCH | `/admin/payments*` | Cập nhật thanh toán |
| GET/PATCH | `/admin/customers*` | Khách hàng + lịch sử đặt phòng (tách khỏi `/admin/users`) |
| GET/PATCH | `/admin/users*` | Tài khoản admin/staff/customer, khóa/mở |
| CRUD | `/admin/promotions*` | Khuyến mãi |
| GET/PATCH/DELETE | `/admin/reviews*` | Duyệt/ẩn/xóa đánh giá |
| CRUD | `/admin/news*`, `/admin/banners*` | Tin tức, banner |
| GET/PATCH/DELETE | `/admin/contact-messages*` | Hộp thư liên hệ |

## Staff (`role:staff`) — phạm vi hẹp hơn admin

| Method | URI | Chức năng |
|---|---|---|
| GET | `/staff/dashboard` | Thống kê (giống admin, không có quản lý user) |
| GET | `/staff/hotel-info` | Chỉ xem, không sửa |
| CRUD (trừ delete) | `/staff/room-types*` | Không xóa được loại phòng |
| GET/POST/PATCH | `/staff/bookings*`, `/staff/payments*` | Giống quyền admin cho booking/payment |

Staff **không có**: `/admin/users`, `/admin/database`, `/admin/customers`,
xóa loại phòng — đúng chủ đích tách biệt quyền hạn.

## API JSON `/api/v1/*` (phụ, xem README mục "API cũ")

`POST /register|login`, `GET /me`, `PUT /profile|change-password`,
`POST /logout`, `GET|POST /bookings*`, `GET|PUT /admin/bookings*`,
`GET /room-types*`, `GET|PUT|PATCH|DELETE /admin/{hotel-info,room-types,users}*`,
`GET /admin/audit-logs`. Toàn bộ đã hoàn thiện (không còn stub) và có test ở
`tests/Feature/Api/`.
