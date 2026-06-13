# BUG REPORT – TUẦN 1-3 (BE4)

**Phụ trách:** BE4 (phát hiện, retest), người sửa ghi theo từng bug
**Phạm vi:** Test framework, CI, Database/Seeder, Auth/RBAC, Postman collection
**Tổng số bug:** 9 (Critical: 1, High: 4, Medium: 4, Low: 0)
**Trạng thái:** Toàn bộ đã **Fixed & Closed**, có minh chứng trong
`docs/test-evidence/phpunit-run-tuan3.txt` (39 passed) và
`docs/test-evidence/postman-run-tuan3.json` / `.html` (26/26 assertion pass).

---

## BUG-FW-01 — Pest không chạy, lỗi "Did you forget to use the pest()->extend() function?"

| Trường | Nội dung |
|---|---|
| Module | Test framework (toàn bộ test viết theo cú pháp Pest: `it()`, `uses()`) |
| Mức độ | **Critical** |
| Trạng thái | Fixed & Closed |

**Bước tái hiện:** Chạy `php artisan test`.

**Kết quả thực tế:** `Error: Call to undefined method
Tests\Feature\HealthCheckTest::getJson()`. 37/38 test fail vì Pest chưa được
"bootstrap" với `Tests\TestCase` của Laravel.

**Nguyên nhân:** Thiếu file `tests/Pest.php` (file cấu hình bắt buộc của Pest
4 để map các test Pest-style vào `Tests\TestCase`).

**Đã sửa:** Tạo `tests/Pest.php`:
```php
uses(TestCase::class)->in('Feature', 'Unit');
```

**Retest:** `php artisan test` → 39 passed. ✅

---

## BUG-FW-02 — Test DB cấu hình MySQL nhưng môi trường không có driver/service MySQL

| Trường | Nội dung |
|---|---|
| Module | `phpunit.xml` / CI |
| Mức độ | **High** |
| Trạng thái | Fixed & Closed |

**Bước tái hiện:** Chạy `php artisan test` với `phpunit.xml` cấu hình
`DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`.

**Kết quả thực tế:** `QueryException: could not find driver (Connection:
mysql, ...)`.

**Nguyên nhân:** Test suite phụ thuộc MySQL service container, nhưng tài liệu
`docs/test-auth.md` lại ghi môi trường test là "SQLite in-memory" — không
đồng bộ. Chạy local/CI không có MySQL sẽ luôn fail.

**Đã sửa:** Đổi `phpunit.xml` sang `DB_CONNECTION=sqlite`,
`DB_DATABASE=:memory:`. Tạo `.github/workflows/laravel-tests.yml` chạy test
bằng SQLite, không cần service ngoài. Cập nhật
`docs/check-list/TC_BE4_Tuan2_Database_CI_Checklist.md`.

**Retest:** `php artisan test` chạy < 1s, không cần DB ngoài. ✅

---

## BUG-FW-03 — `/api/health` không trả đủ cấu trúc theo `HealthCheckTest`

| Trường | Nội dung |
|---|---|
| Module | Health check |
| Mức độ | **Medium** |
| Trạng thái | Fixed & Closed |

**Bước tái hiện:** `GET /api/health`.

**Kết quả thực tế:** `{"success": true, "status": "ok"}` — thiếu `message` và
`data.app/env/time/database` mà `HealthCheckTest` yêu cầu.

**Đã sửa:** Tạo `App\Http\Controllers\Api\HealthController` trả về:
```json
{
  "success": true,
  "message": "Server is running",
  "data": { "app": "...", "env": "...", "time": "...", "database": "ok" }
}
```
Cập nhật `routes/api.php` để dùng controller này.

**Retest:** `HealthCheckTest` pass; Postman "Health Check" pass 3/3 assertion. ✅

---

## BUG-DB-01 — `DatabaseSeedTest` kỳ vọng bảng/cột chưa tồn tại và seed thiếu user bị khóa

| Trường | Nội dung |
|---|---|
| Module | Database / Seeder |
| Mức độ | **High** |
| Trạng thái | Fixed & Closed |

**Bước tái hiện:** Chạy `tests/Feature/DatabaseSeedTest.php`.

**Kết quả thực tế:**
- `Bảng 'room_type_amenity' chưa được tạo` (chưa có migration — thuộc phạm vi
  Tuần 6, BE2).
- `User::where('is_active', false)` — cột `is_active` không tồn tại (cột thật
  là `status` enum `active/locked`).
- `UserSeeder` chỉ tạo 3 user (customer/staff/admin), thiếu user bị khóa theo
  yêu cầu checklist Tuần 1 ("Seed tạo đủ 3 role user + 1 user bị khóa").

**Đã sửa:**
1. `UserSeeder`: thêm user `locked@homi.test` với `status = 'locked'`.
2. `DatabaseSeedTest`: bỏ `room_type_amenity` khỏi danh sách bảng bắt buộc
   (ghi chú sẽ bổ sung lại ở Tuần 6 khi BE2 tạo migration); đổi
   `is_active` → `status === 'locked'`.

**Retest:** `DatabaseSeedTest` 3/3 pass. ✅

---

## BUG-AUTH-01 — Postman collection dùng `/v1/auth/...` nhưng route thực tế là `/v1/...` (không có prefix `auth`)

| Trường | Nội dung |
|---|---|
| Module | Auth / RBAC / Postman |
| Mức độ | **High** |
| Trạng thái | Fixed & Closed |

**Bước tái hiện:** Chạy Postman collection `Homi-Backend-v1` (folder Auth, RBAC).

**Kết quả thực tế:** Toàn bộ request `Login`, `Get Me`, `Update Profile`,
`Logout`, `Bookings Ping`... trả `404 Not Found` vì gọi
`/api/v1/auth/login`, `/api/v1/auth/me`,... trong khi `routes/api.php` định
nghĩa `/api/v1/login`, `/api/v1/me`,... (đúng với `LoginTest`, `RbacTest` đang
pass). Tương tự, request "Toggle Active" gọi `.../toggle-active` nhưng route
là `.../toggle-status`; "Bookings Ping" gọi `/v1/bookings/ping` — route này
không còn tồn tại (BE3 đã code thẳng các route booking thật).

**Nguyên nhân:** API contract v1 (`docs/check-list/TC_BE4_Tuan2_API_Contract_v1.md`)
và Postman collection được soạn theo một thiết kế route khác với route mà
BE1/BE3 đã thực hiện thực tế (route thực tế không dùng prefix `/auth`).

**Đã sửa:**
- Sửa toàn bộ URL trong `Homi-Backend-v1.postman_collection.json`:
  `/v1/auth/*` → `/v1/*`, `toggle-active` → `toggle-status` (target user id
  `1`), `Bookings Ping` → `GET /v1/bookings` (đổi tên thành "My Bookings -
  Customer (200)" / "My Bookings - Admin (403)").
- Cập nhật `docs/check-list/TC_BE4_Tuan2_API_Contract_v1.md` cho khớp route
  thực tế (đã code đến route booking Tuần 10-12 dạng skeleton).

**Retest:** Toàn bộ request không còn 404. ✅

---

## BUG-AUTH-02 — Postman dùng sai mật khẩu seed (`password` thay vì `123456`) khiến Login luôn 401

| Trường | Nội dung |
|---|---|
| Module | Auth / Postman |
| Mức độ | **High** |
| Trạng thái | Fixed & Closed |

**Bước tái hiện:** Request "Login - Admin/Staff/Customer" với body
`{"email": "...@homi.test", "password": "password"}`.

**Kết quả thực tế:** `401 Unauthorized` — sai như trường hợp "Login - Sai mat
khau (Fail case)", dù dùng đúng email seed.

**Nguyên nhân:** `UserSeeder` tạo mật khẩu `Hash::make('123456')` cho mọi tài
khoản demo, nhưng Postman request dùng `"password": "password"`.

**Đã sửa:** Đổi `password` trong body của "Login - Admin", "Login - Staff",
"Login - Customer", "Login - Tai khoan bi khoa (Fail case)" thành `123456`.

**Retest:** Login Admin/Staff/Customer → 200, token được set vào environment;
"Tai khoan bi khoa" → 403 đúng. ✅

---

## BUG-AUTH-03 — `PUT /api/v1/profile` bắt buộc `email`, không cho phép cập nhật một phần (chỉ tên/sđt)

| Trường | Nội dung |
|---|---|
| Module | Auth – `UpdateProfileRequest` |
| Mức độ | **Medium** |
| Trạng thái | Fixed & Closed |

**Bước tái hiện:** `PUT /api/v1/profile` với body `{"name": "...", "phone":
"0911111111"}` (không có `email`).

**Kết quả thực tế:** `422 Unprocessable Content`, lỗi "Email không được để
trống." — chặn cả việc Postman "Update Profile - Customer" pass.

**Đã sửa:** Đổi rule `email` từ `['required', 'email', Rule::unique(...)]`
thành `['sometimes', 'required', 'email', Rule::unique(...)]` trong
`UpdateProfileRequest` — cho phép cập nhật một phần (không gửi `email` thì
giữ nguyên email cũ).

**Test bổ sung:** `ProfileTest::test_update_profile_allows_partial_update_without_email`.
**Test đã cập nhật:** `test_update_profile_fails_when_required_fields_missing`
chỉ còn yêu cầu lỗi ở `name`.

**Retest:** `ProfileTest` pass tất cả; Postman "Update Profile - Customer"
→ 200, `data.user.name` đúng. ✅

---

## BUG-RBAC-01 — Postman: `Logout - Customer` chạy giữa collection làm hỏng `token_customer` cho các test RBAC/Booking phía sau

| Trường | Nội dung |
|---|---|
| Module | Postman collection (thứ tự request) |
| Mức độ | **Medium** |
| Trạng thái | Fixed & Closed |

**Bước tái hiện:** Chạy collection theo thứ tự gốc: folder `Auth` (có
`Logout - Customer` ở cuối) → folder `RBAC - Admin Users` / `RBAC - Booking
Route Skeleton` (dùng lại `{{token_customer}}`).

**Kết quả thực tế:** Các request RBAC dùng `token_customer` trả `401`
(token đã bị revoke bởi Logout) thay vì `403`/`200` như kỳ vọng.

**Đã sửa:** Tách `Logout - Customer` và `Get Me - Token da logout (Fail case)`
ra folder mới **"Cleanup - Logout Flow (chạy sau cùng)"**, đặt ở cuối
collection — sau toàn bộ RBAC/Booking.

**Retest:** Chạy lại toàn bộ collection theo thứ tự mới → 26/26 assertion
pass. ✅

---

## BUG-RBAC-02 — Test kỳ vọng `staff` bị `403` khi `GET /admin/users`, nhưng route cho phép `admin,staff`

| Trường | Nội dung |
|---|---|
| Module | Postman test expectation (clarification, không phải lỗi code) |
| Mức độ | **Medium** |
| Trạng thái | Fixed & Closed (cập nhật expectation) |

**Bước tái hiện:** "List Users - Staff (403)" → `GET /v1/admin/users` với
token staff.

**Kết quả thực tế:** `200 OK` (route middleware là `role:admin,staff` — staff
được xem danh sách user, chỉ riêng `toggle-status` mới giới hạn `admin`).

**Phân tích:** Đây là **thiết kế đúng** theo `routes/api.php` hiện tại (staff
được hỗ trợ xem danh sách user để phục vụ công việc lễ tân/quản lý, nhưng
không được khóa/mở tài khoản). API contract và Postman trước đó kỳ vọng sai
(403). Nhóm xác nhận giữ nguyên route hiện tại.

**Đã sửa:** Đổi tên request thành "List Users - Staff (200)", sửa assertion
thành `200`. Cập nhật ghi chú phân quyền trong
`docs/check-list/TC_BE4_Tuan2_API_Contract_v1.md`.

**Retest:** Pass. ✅

---

## BUG-RBAC-03 — Postman test "Filter role=customer" đọc sai cấu trúc response (`data` thay vì `data.users`)

| Trường | Nội dung |
|---|---|
| Module | Postman test script |
| Mức độ | **Low/Medium** |
| Trạng thái | Fixed & Closed |

**Bước tái hiện:** "List Users - Filter role=customer" → script
`pm.response.json().data.every(...)`.

**Kết quả thực tế:** `TypeError: items.every is not a function` — vì
`AdminUserController::index` trả `data: { users: [...], meta: {...} }` (qua
helper `paginated()`), không phải `data` là array trực tiếp.

**Đã sửa:** Sửa script thành `pm.response.json().data.users.every(...)`.

**Retest:** Pass. ✅

---

## Tổng kết

| Mã bug | Mức độ | Trạng thái |
|---|---|---|
| BUG-FW-01 | Critical | Closed |
| BUG-FW-02 | High | Closed |
| BUG-FW-03 | Medium | Closed |
| BUG-DB-01 | High | Closed |
| BUG-AUTH-01 | High | Closed |
| BUG-AUTH-02 | High | Closed |
| BUG-AUTH-03 | Medium | Closed |
| BUG-RBAC-01 | Medium | Closed |
| BUG-RBAC-02 | Medium | Closed |
| BUG-RBAC-03 | Low/Medium | Closed |

**Minh chứng:**
- `docs/test-evidence/phpunit-run-tuan3.txt` — 39 passed (129 assertions).
- `docs/test-evidence/postman-run-tuan3.json` /
  `docs/test-evidence/postman-run-tuan3.html` — 20 requests, 26/26 assertion
  pass, 0 failed.
