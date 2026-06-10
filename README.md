# Homi - Website Đặt Phòng Khách Sạn

Backend API cho hệ thống đặt phòng khách sạn Homi.  
Công nghệ: **Laravel 13**, MySQL, Laravel Sanctum, REST API `/api/v1`.

---

## Yêu cầu môi trường

| Phần mềm | Phiên bản tối thiểu |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| Node.js (tùy chọn, cho frontend) | 18+ |

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

# 4. Tạo application key
php artisan key:generate

# 5. Cấu hình database trong .env
# DB_DATABASE=homi
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Chạy migration và seed dữ liệu mẫu
php artisan migrate --seed

# 7. Khởi động server local
php artisan serve
```

API sẽ chạy tại: `http://localhost:8000/api/v1`

---

## Tài khoản demo (sau khi seed)

| Role | Email | Mật khẩu |
|---|---|---|
| Admin | admin@homi.vn | password |
| Staff | staff@homi.vn | password |
| Customer | customer@homi.vn | password |

---

## Cấu trúc thư mục chính

```
app/
├── Http/
│   ├── Controllers/Api/   # API Controllers (versioned)
│   ├── Requests/          # FormRequest validation theo module
│   └── Middleware/        # RoleMiddleware, ...
├── Models/                # Eloquent Models
├── Services/              # Business logic
├── Repositories/          # Database queries (nếu dùng)
└── Traits/
    └── ApiResponse.php    # Chuẩn response JSON dùng chung
routes/
├── api.php                # API routes, prefix /api/v1
└── web.php                # Web routes (Blade)
database/
├── migrations/
└── seeders/
```

---

## Chuẩn Response API

Mọi API đều trả về JSON theo định dạng thống nhất:

**Thành công:**
```json
{
  "success": true,
  "message": "...",
  "data": { ... }
}
```

**Lỗi:**
```json
{
  "success": false,
  "message": "...",
  "errors": { ... }
}
```

**Phân trang:**
```json
{
  "success": true,
  "data": {
    "items": [ ... ],
    "meta": {
      "current_page": 1,
      "per_page": 15,
      "total": 100,
      "last_page": 7
    }
  }
}
```

---

## Mã lỗi HTTP

| Mã | Ý nghĩa |
|---|---|
| 200 | Thành công |
| 201 | Tạo mới thành công |
| 401 | Chưa đăng nhập / sai thông tin đăng nhập |
| 403 | Không có quyền |
| 404 | Không tìm thấy |
| 422 | Dữ liệu không hợp lệ (validation) |
| 500 | Lỗi server |

---

## API Endpoints (v1)

### Auth
| Method | Endpoint | Mô tả | Auth |
|---|---|---|---|
| POST | `/api/v1/register` | Đăng ký | Không |
| POST | `/api/v1/login` | Đăng nhập | Không |
| GET | `/api/v1/me` | Thông tin tài khoản | Bearer token |
| PUT | `/api/v1/profile` | Cập nhật hồ sơ | Bearer token |
| POST | `/api/v1/logout` | Đăng xuất | Bearer token |

---

## Chạy kiểm thử

```bash
php artisan test
```

---

## Quy trình Git

- Branch chính: `main`
- Mỗi tính năng tạo branch riêng: `feature/<tên>`
- Mỗi bug fix: `fix/<tên>`
- Tạo Pull Request, cần ít nhất 1 người review trước khi merge
- Xem mẫu PR tại: `.github/PULL_REQUEST_TEMPLATE.md`
