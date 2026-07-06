# Homi — Website Đặt Phòng Khách Sạn

Website đặt phòng và quản lý cho **1 khách sạn duy nhất** (không phải nền
tảng đa khách sạn kiểu OTA). Kiến trúc chính là **Laravel Blade monolith**:
controller trả thẳng về view Blade, có session/CSRF, không cần frontend
riêng để chạy. Công nghệ: **Laravel 13**, PHP 8.3, MySQL, Vite + Tailwind.

> Dự án còn giữ lại một tầng REST API (`/api/v1/*`, dùng Laravel Sanctum) từ
> giai đoạn đầu — xem mục [API cũ](#api-cũ-không-phải-trọng-tâm) ở cuối file.
> Sản phẩm chính để demo/nghiệm thu là 3 khu vực Blade: `/` (public),
> `/customer/*`, `/admin/*` và `/staff/*`.

---

## Yêu cầu môi trường

| Phần mềm | Phiên bản tối thiểu |
|---|---|
| PHP | 8.3 |
| Composer | 2.x |
| MySQL | 8.0+ (hoặc SQLite nếu không cài MySQL) |
| Node.js | 20+ |

---

## Cài đặt nhanh

```bash
git clone <repo-url>
cd homi-hotel-booking
composer run setup
```

`composer run setup` chạy đủ các bước: `composer install` → copy `.env` →
`key:generate` → `migrate --seed` → `storage:link` → `npm install` →
`npm run build`.

Sau khi setup, sửa 2 biến trong `.env` cho khớp máy bạn: `DB_DATABASE` /
`DB_USERNAME` / `DB_PASSWORD` (MySQL local) và `APP_URL`.

Chạy server:

```bash
php artisan serve      # nếu không dùng virtual host (Laragon/Herd/Valet)
npm run dev             # tùy chọn, hot-reload khi sửa CSS/JS
```

Mở `http://localhost:8000` (hoặc domain virtual host của bạn).

Checklist đầy đủ hơn (smoke test, security checklist khi deploy) xem
[`docs/check-list/Staging_Checklist_Tuan14.md`](docs/check-list/Staging_Checklist_Tuan14.md).

---

## Tài khoản demo (sau khi seed)

| Role | Email | Mật khẩu | Đăng nhập tại |
|---|---|---|---|
| Admin | `admin@homi.test` | `123456` | `/admin/login` |
| Staff | `staff@homi.test` | `123456` | `/admin/login` (redirect theo role) |
| Customer | `customer@homi.test` | `123456` | `/customer/login` |
| Customer phụ | `user@gmail.com` | `123456` | dùng để test không xem được đơn người khác |
| Customer đã khóa | `locked@homi.test` | `123456` | dùng để test tài khoản bị khóa |

Chi tiết dữ liệu mẫu (mã đơn, mã khuyến mãi...) xem
[`docs/demo-scripts/DemoScript_Tuan10-13.md`](docs/demo-scripts/DemoScript_Tuan10-13.md).
Kịch bản demo đầy đủ **toàn bộ chức năng** (kể cả giữ chỗ, giá theo mùa,
khuyến mãi stack, dịch vụ thêm, bản đồ, hóa đơn nội bộ, đặt đoàn/nhóm, lễ
tân & buồng phòng) xem
[`docs/demo-scripts/DemoScript_TatCaChucNang.md`](docs/demo-scripts/DemoScript_TatCaChucNang.md).

---

## Route chính theo khu vực

| Khu vực | Ví dụ route | Middleware |
|---|---|---|
| Public | `/`, `/rooms`, `/rooms/{id}`, `/promotions`, `/news`, `/contact`, `/about` | không cần đăng nhập |
| Customer | `/customer/login`, `/customer/dashboard`, `/customer/bookings`, `/customer/profile`, `/customer/wishlist`, `/customer/reviews/create` | `auth`, `role:customer` |
| Admin | `/admin/dashboard`, `/admin/room-types`, `/admin/bookings`, `/admin/payments`, `/admin/customers`, `/admin/users`, `/admin/promotions`, `/admin/reviews`, `/admin/news`, `/admin/banners`, `/admin/contact-messages` | `role:admin` |
| Staff | `/staff/dashboard`, `/staff/room-types`, `/staff/bookings`, `/staff/payments`, `/staff/hotel-info` | `role:staff` — phạm vi hẹp hơn admin (không có users/database/xóa loại phòng) |

Xem đầy đủ: `php artisan route:list`.

---

## Cấu trúc thư mục chính

```
app/
├── Http/
│   ├── Controllers/Web/      # Controller Blade — sản phẩm chính (Admin/Staff/Customer)
│   ├── Controllers/Api/      # API cũ /api/v1 — xem mục "API cũ" cuối file
│   ├── Requests/             # FormRequest validation theo module
│   ├── Middleware/           # RoleMiddleware, CheckActiveAccount
│   └── Policies/             # BookingPolicy, RoomTypePolicy, HotelInfoPolicy
├── Models/                   # Eloquent Models
├── Services/                 # Business logic (BookingService, AvailabilityService, PricingService...)
└── Enums/                    # BookingStatus, PaymentStatus, PaymentMethod
resources/views/
├── admin/, staff/, customer/ # View theo khu vực
└── rooms/, layouts/, partials/
routes/
├── web.php                   # Route Blade — routes chính của dự án
└── api.php                   # Route API cũ, prefix /api/v1
database/
├── migrations/
└── seeders/                  # DatabaseSeeder — chạy migrate --seed để có data demo đầy đủ
docs/
├── check-list/               # Bug report, staging checklist theo từng sprint
├── demo-scripts/             # Kịch bản demo
└── test-cases/, test-evidence/
```

---

## Chạy kiểm thử

```bash
php artisan test
```

Test suite hiện tại: xem số liệu mới nhất trong
[`docs/check-list/TestReport_Final_Tuan15.md`](docs/check-list/TestReport_Final_Tuan15.md).
Toàn bộ luồng nghiệp vụ lõi (auth/RBAC, hotel/room, booking/availability/
payment, admin extras) đều có test feature/unit tương ứng trong `tests/`.

---

## Quy trình Git

- Branch chính: `main`
- Mỗi tính năng tạo branch riêng: `feature/<tên>`
- Mỗi bug fix: `fix/<tên>`
- Tạo Pull Request, cần ít nhất 1 người review trước khi merge
- Xem mẫu PR tại: `.github/PULL_REQUEST_TEMPLATE.md`

---

## API cũ (không phải trọng tâm)

`app/Http/Controllers/Api/*` + `routes/api.php` (prefix `/api/v1`) là tầng
REST API dùng Laravel Sanctum, còn sót lại từ giai đoạn đầu dự án trước khi
nhóm chuyển hẳn sang Blade monolith. Đã được hoàn thiện đầy đủ (không còn
stub) và có test (`tests/Feature/Api/`), nhưng **không phải sản phẩm chính**
để demo — luồng nghiệp vụ thật (đặt phòng, quản trị, thanh toán) chạy trên
Blade (`/customer`, `/admin`, `/staff`).

Chuẩn response chung:

```json
// Thành công
{ "success": true, "message": "...", "data": { ... } }

// Lỗi
{ "success": false, "message": "...", "errors": { ... } }
```

Nhóm endpoint chính: `POST /api/v1/register|login`, `GET /api/v1/me`,
`GET|POST /api/v1/bookings*`, `GET /api/v1/room-types*`,
`GET|PUT /api/v1/admin/*` (hotel-info, room-types, users, bookings,
audit-logs — cần `Authorization: Bearer <token>` + đúng role).
