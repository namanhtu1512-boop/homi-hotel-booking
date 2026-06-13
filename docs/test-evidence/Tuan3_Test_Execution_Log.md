# TEST EXECUTION LOG – TUẦN 3 (BE4)

**Ngày chạy:** xem timestamp trong `postman-run-tuan3.json`
**Người chạy:** BE4
**Môi trường:** PHP 8.3, Laravel 13, SQLite (`:memory:` cho Pest, file
`database/database.sqlite` cho Postman/Newman), `php artisan serve` tại
`http://127.0.0.1:8000`.

## 1. Automated test (Pest/PHPUnit)

Lệnh chạy:
```bash
php artisan test
```

Kết quả: **39/39 passed (129 assertions)**. Log đầy đủ:
`docs/test-evidence/phpunit-run-tuan3.txt`.

Các test liên quan Auth/RBAC:

| Test file | Số test | Kết quả |
|---|---|---|
| `tests/Feature/Auth/RegisterTest.php` | 8 | ✅ Pass |
| `tests/Feature/Auth/LoginTest.php` | 6 | ✅ Pass |
| `tests/Feature/Auth/ProfileTest.php` | 9 | ✅ Pass (đã bổ sung TC partial-update) |
| `tests/Feature/Auth/RbacTest.php` | 7 | ✅ Pass |
| `tests/Feature/DatabaseSeedTest.php` | 3 | ✅ Pass |
| `tests/Feature/HealthCheckTest.php` | 1 | ✅ Pass |
| `tests/Feature/ExampleTest.php` | 1 | ✅ Pass |
| `tests/Unit/ExampleTest.php` | 1 | ✅ Pass |

## 2. Postman / Newman (theo role)

Lệnh chạy:
```bash
php artisan migrate:fresh --seed --force
php artisan serve --port=8000   # chạy nền

newman run tests/Postman/Homi-Backend-v1.postman_collection.json \
  -e tests/Postman/Homi-Local.postman_environment.json \
  --reporters cli,htmlextra,json \
  --reporter-htmlextra-export docs/test-evidence/postman-run-tuan3.html \
  --reporter-json-export docs/test-evidence/postman-run-tuan3.json
```

Kết quả: **20/20 request chạy, 26/26 assertion pass, 0 failed**.

| Folder | Số request | Kết quả |
|---|---|---|
| Health | 1 | ✅ |
| Auth (register, login theo 3 role, profile, ...) | 9 | ✅ |
| RBAC - Admin Users (theo role admin/staff/customer) | 5 | ✅ |
| RBAC - Booking Route Skeleton (customer 200 / admin 403) | 2 | ✅ |
| Cleanup - Logout Flow (chạy sau cùng) | 2 | ✅ |
| Register fail case | 1 | ✅ |

Báo cáo chi tiết (định dạng HTML, có thể mở bằng browser để xem từng
request/response — dùng làm minh chứng thay ảnh chụp Postman Desktop):
`docs/test-evidence/postman-run-tuan3.html`.

## 3. Bảo mật cơ bản đã kiểm tra

- `password` **không** xuất hiện trong response của `/register`, `/login`,
  `/me` (test `password_not_in_login_response`, Postman "Khong co password").
- Token bị revoke (`logout`) → request sau đó trả `401`
  (`test_revoked_token_returns_401`, Postman "Get Me - Token da logout").
- Tài khoản `status = locked` không đăng nhập được, trả `403`
  (`test_inactive_account_cannot_login`, Postman "Login - Tai khoan bi khoa").

## 4. Bug phát hiện trong tuần

Xem chi tiết: `docs/bug-reports/BUG_LIST_Tuan1-3_BE4.md` (9 bug, tất cả đã
Fixed & Closed, có retest pass).

## 5. Kết luận

Auth/RBAC module đạt tiêu chí nghiệm thu Tuần 3 theo
`docs/check-list/Checklist_Nghiem_Thu_MVP.md`. Sẵn sàng cho Tuần 4 (core
backend ổn định, exception handler, audit log).
