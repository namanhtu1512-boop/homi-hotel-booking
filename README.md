# Homi - Website Đặt Phòng Khách Sạn

Homi là website đặt phòng và quản trị cho **1 khách sạn duy nhất** — không phải nền tảng nhiều khách sạn.

**Kiến trúc:** Laravel 13 Blade monolith (server-side rendering). Controller trả thẳng về view Blade, **không có tầng REST API/JSON riêng** — toàn bộ ứng dụng chỉ có 1 giao diện web duy nhất nên không cần tách API để client ngoài tiêu thụ JSON. Mọi route đều nằm trong `routes/web.php`.

---

## Yêu cầu môi trường

| Phần mềm | Phiên bản tối thiểu |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL (khuyến nghị) hoặc SQLite | 8.0+ / bất kỳ |
| Node.js (tùy chọn, cho asset frontend) | 18+ |

---

## Cài đặt

```bash
# 1. Clone repository
git clone <repo-url>
cd homi-hotel-booking

# 2. Cài đặt dependencies
composer install

# 3. Sao chép file cấu hình
cp .env.example .env
php artisan key:generate

# 4a. Chạy nhanh bằng SQLite (mặc định trong .env.example, không cần cài DB server)
touch database/database.sqlite   # Windows: dùng New-Item thay cho touch

# 4b. Hoặc dùng MySQL: sửa .env
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_DATABASE=homi
#   DB_USERNAME=root
#   DB_PASSWORD=

# 5. Chạy migration và seed dữ liệu mẫu (xóa sạch DB cũ nếu có)
php artisan migrate:fresh --seed

# 6. Khởi động server local
php artisan serve
```

Ứng dụng chạy tại: `http://localhost:8000`

---

## Tài khoản demo (sau khi seed)

| Role | Email | Mật khẩu | Đăng nhập tại |
|---|---|---|---|
| Admin | admin@homi.test | 123456 | `/admin/login` |
| Staff | staff@homi.test | 123456 | `/admin/login` |
| Customer | customer@homi.test | 123456 | `/customer/login` |

Sau khi đăng nhập, hệ thống tự chuyển hướng theo vai trò: admin → `/admin/dashboard`, staff → `/staff/dashboard`, customer → `/customer/dashboard`. Truy cập sai khu vực (vd. customer vào `/admin`) sẽ bị chặn và chuyển hướng về đúng khu vực của mình.

---

## Cấu trúc thư mục chính

```
app/
├── Http/
│   ├── Controllers/Web/         # Controller Blade — Public, Customer, Admin, Staff
│   ├── Requests/                 # FormRequest validation theo module
│   └── Middleware/                # RoleMiddleware, CheckActiveAccount
├── Models/                        # Eloquent Models
├── Services/                      # Business logic (Booking, Availability, Pricing, ...)
├── Policies/                      # BookingPolicy (customer chỉ xem/hủy đơn của mình)
├── Enums/                         # BookingStatus, PaymentStatus, PaymentMethod
└── Console/Commands/              # QaChecklistCommand, BackupDatabaseCommand
routes/
└── web.php                        # Toàn bộ route — không có routes/api.php
database/
├── migrations/
└── seeders/
resources/views/
├── rooms/, client/                # Trang public
├── customer/                      # Khu vực khách hàng
├── admin/                         # Khu vực admin
└── staff/                         # Khu vực nhân viên (tách biệt admin)
```

---

## Bản đồ route theo khu vực

| Khu vực | Route tiêu biểu | Ghi chú |
|---|---|---|
| Public | `/`, `/about`, `/rooms`, `/rooms/{id}`, `/promotions`, `/news`, `/contact` | Không cần đăng nhập |
| Customer | `/customer/login`, `/customer/register`, `/customer/dashboard`, `/customer/bookings`, `/customer/bookings/create`, `/customer/profile`, `/customer/wishlist`, `/customer/reviews/create` | Cần role `customer` |
| Admin | `/admin/login`, `/admin/dashboard`, `/admin/hotel-info`, `/admin/room-types`, `/admin/bookings`, `/admin/payments`, `/admin/customers`, `/admin/users`, `/admin/promotions`, `/admin/banners`, `/admin/reviews`, `/admin/news`, `/admin/contact-messages`, `/admin/audit-logs`, `/admin/database` | Chỉ role `admin` |
| Staff | `/staff/dashboard`, `/staff/hotel-info` (chỉ xem), `/staff/room-types`, `/staff/bookings`, `/staff/payments` | Chỉ role `staff` — khu vực riêng, **không** có quản lý người dùng/khách hàng, xóa loại phòng, sửa thông tin khách sạn, hay xem database thô |

Xem đầy đủ 111 route bằng lệnh:

```bash
php artisan route:list
```

---

## Luồng nghiệp vụ lõi

1. Khách xem `/rooms`, lọc theo giá/sức chứa/tiện ích, xem chi tiết phòng.
2. Khách chọn ngày nhận/trả phòng, hệ thống kiểm tra phòng trống theo overlap ngày (`AvailabilityService`, 5 case: trùng hoàn toàn, giao đầu, giao cuối, nằm trong, bao ngoài — đều có test).
3. Khách đăng nhập/đăng ký, tạo đơn đặt phòng (`BookingService::create()` — chạy trong DB transaction, khóa hàng `room_types` bằng `lockForUpdate()` khi re-check chỗ trống để 2 người đặt cùng lúc không thể cùng vượt qua số lượng phòng).
4. Khách xem/hủy đơn của mình tại `/customer/bookings` (chỉ pending/confirmed, trước ngày check-in).
5. Admin/staff xác nhận đơn, cập nhật trạng thái thanh toán mô phỏng tại `/admin/bookings` hoặc `/staff/bookings`.
6. Admin xem thống kê tại `/admin/dashboard`: tổng đơn, đơn theo trạng thái, tỷ lệ hủy, tỷ lệ lấp đầy, doanh thu mô phỏng theo tháng.

---

## Chạy kiểm thử

```bash
php artisan test
```

Test dùng SQLite in-memory (cấu hình sẵn trong `phpunit.xml`), không cần chuẩn bị DB riêng. CI chạy tự động qua GitHub Actions (`.github/workflows/laravel-tests.yml`) trên mỗi push/PR vào `main`/`develop`.

Phạm vi test chính: auth/RBAC, CRUD hotel-info/room-types (kể cả validation giá/số lượng âm), search/filter phòng (kèm test hiệu năng chống N+1), toàn bộ 5 case overlap availability, luồng booking E2E, hủy đơn, quản lý booking/payment admin lẫn staff, dashboard đối chiếu DB, review, wishlist.

---

## Sao lưu database

```bash
php artisan homi:backup-database
```

Tự nhận diện driver đang dùng: `mysqldump` cho MySQL, copy file cho SQLite. File backup lưu tại `storage/app/backups/`.

---

## Quy trình Git

- Branch chính: `main`
- Mỗi tính năng tạo branch riêng: `feature/<tên>`
- Mỗi bug fix: `fix/<tên>`
- Tạo Pull Request, cần ít nhất 1 người review trước khi merge
- Xem mẫu PR tại: `.github/PULL_REQUEST_TEMPLATE.md`

---

## Giới hạn đã biết (Known limitations)

- **Thanh toán là mô phỏng**, không tích hợp cổng thanh toán thật (đúng phạm vi đồ án: "thanh toán mô phỏng").
- **Race-condition khi đặt phòng** được chống bằng khóa hàng `room_types` (`SELECT ... FOR UPDATE`) trong transaction, nhưng chưa có test tự động mô phỏng 2 request đồng thời thật sự (SQLite in-memory dùng cho test không hỗ trợ nhiều kết nối song song) — đã kiểm tra logic thủ công và qua code review.
- **Giá phòng cố định mỗi đêm**, chưa hỗ trợ giá theo mùa/cuối tuần hay phụ thu trẻ em (thuộc mục "có thời gian thì mở rộng" trong kế hoạch, không bắt buộc).
- Ảnh khách sạn/phòng hỗ trợ cả upload file thật lẫn nhập đường dẫn URL (textarea), tùy màn hình.
- Module dịch vụ/ưu đãi/đánh giá làm ở mức vừa đủ để demo, không ảnh hưởng luồng đặt phòng lõi.

---

## Release notes (bản audit gần nhất)

- Gỡ bỏ tầng REST API cũ (`routes/api.php`, `app/Http/Controllers/Api/*`) vì không còn khớp với kiến trúc Blade-only đã chốt — toàn bộ nghiệp vụ tương ứng đã có sẵn ở route Blade.
- Vá lỗ hổng race condition khi tạo booking (khóa hàng `room_types` trong transaction).
- Bổ sung tiện ích riêng cho từng loại phòng, nút ẩn/hiện phòng, lọc phòng theo tiện ích.
- Tách `/admin/customers` (CRM khách hàng, xem lịch sử đặt phòng) khỏi `/admin/users` (quản lý tài khoản).
- Bổ sung tỷ lệ hủy đơn trên dashboard, trang lỗi 401/422, lệnh sao lưu database.
- Dọn seeder trùng lặp dữ liệu phòng; seed đầy đủ khuyến mãi/banner.
