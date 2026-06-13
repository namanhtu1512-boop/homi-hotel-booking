# TEST PLAN TỔNG QUÁT – DỰ ÁN HOMI (BACKEND)

**Phụ trách:** BE4 (chính), BE1 (review)
**Phiên bản:** v1 – Tuần 1 (cập nhật tới Tuần 15 theo kế hoạch)
**Dự án:** Website đặt phòng khách sạn Homi – Backend Laravel 13

---

## 1. Mục tiêu kiểm thử

- Đảm bảo các module backend (auth/RBAC, hotel, room type, search, availability,
  booking, payment mô phỏng, admin, thống kê) hoạt động đúng theo API contract và
  nghiệp vụ đã thống nhất trong kế hoạch 16 tuần.
- Phát hiện sớm lỗi nghiệp vụ, đặc biệt là lỗi liên quan đến **kiểm tra phòng
  trống** và **tạo đơn đặt phòng** (lõi của hệ thống Homi).
- Đảm bảo mỗi API quan trọng có: API contract, dữ liệu test, test case
  pass/fail, và minh chứng (evidence) chạy được.
- Cung cấp bộ test tự động (Pest/PHPUnit) + bộ Postman collection để CI và
  nhóm có thể chạy lại bất cứ lúc nào.

## 2. Phạm vi kiểm thử

### Trong phạm vi (in-scope)
- API xác thực, phân quyền (RBAC: customer/staff/admin).
- API quản lý khách sạn, loại phòng, giá, tồn phòng (CRUD + public API).
- API tìm kiếm, lọc, chi tiết khách sạn/phòng.
- API kiểm tra phòng trống, chống đặt trùng (overlap).
- API tạo đơn, tính tiền, hủy đơn, quản lý đơn (customer + admin/staff).
- API thanh toán mô phỏng, thống kê cơ bản.
- Database: migration, seeder, ràng buộc khóa ngoại, soft delete.
- Health-check, cấu hình môi trường, CI pipeline.

### Ngoài phạm vi (out-of-scope)
- Thanh toán thật qua cổng thanh toán bên thứ ba (chỉ mô phỏng).
- Gửi email/SMS thật (chỉ notification mô phỏng/log).
- Kiểm thử hiệu năng tải lớn (load test) ngoài mức "stress test cơ bản" ở
  tuần 14.
- Frontend UI/UX (chỉ kiểm thử qua API).

## 3. Môi trường kiểm thử

| Thành phần | Giá trị |
|---|---|
| PHP | 8.3 |
| Framework | Laravel 13 |
| Test framework | Pest 4 (chạy trên PHPUnit 12) |
| Database test | SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) – nhanh, không cần service ngoài, dùng cho local & CI |
| Database local/staging demo | SQLite file (`database/database.sqlite`) hoặc MySQL khi deploy |
| API test | Postman/Newman (`tests/Postman/`) |
| CI | GitHub Actions (`.github/workflows/laravel-tests.yml`) |

> Quyết định: dùng SQLite in-memory cho test suite (thay vì MySQL service
> container) để test chạy nhanh, không phụ thuộc service ngoài và dễ chạy ở
> máy của từng thành viên. Database thật cho demo cuối kỳ (tuần 16) có thể là
> SQLite file hoặc MySQL tuỳ môi trường deploy.

## 4. Loại kiểm thử

| Loại | Công cụ | Mục đích |
|---|---|---|
| Unit/Feature test | Pest/PHPUnit | Kiểm thử logic service, validation, response API |
| API test (manual + automated) | Postman/Newman | Kiểm thử end-to-end theo role, theo flow |
| Database test | Pest (`DatabaseSeedTest`) | Kiểm thử migration, seeder, quan hệ dữ liệu |
| Regression test | Pest + Postman (CI) | Đảm bảo thay đổi mới không phá luồng cũ |
| Smoke test | `/api/health` + Postman Health folder | Kiểm tra server/DB còn sống trước khi demo |

## 5. Tiêu chí Pass / Fail

- **Pass**: API trả đúng HTTP status code, đúng cấu trúc response chuẩn
  (`success`, `message`, `data`/`errors`), đúng dữ liệu mong đợi; toàn bộ test
  Pest/PHPUnit liên quan và Postman collection liên quan đều xanh (0 failed).
- **Fail**: sai status code, sai cấu trúc response, lộ dữ liệu nhạy cảm
  (password, token), sai logic nghiệp vụ (đặc biệt overlap ngày, tính tiền,
  trạng thái đơn).
- Lỗi **Critical/High** phải được sửa **trước khi merge / trước khi qua tuần
  kế tiếp** (theo quy trình làm việc hằng tuần, mục "Thứ 6 – Kiểm thử").

## 6. Rủi ro kiểm thử

| Rủi ro | Mức độ | Giảm thiểu |
|---|---|---|
| Sai logic kiểm tra phòng trống (overlap ngày) | Rất cao | Bộ test overlap đầy đủ từ tuần 9, không nghiệm thu nếu sai case |
| Test framework/CI chưa cấu hình đúng (Pest, DB driver...) | Cao | Đã rà soát và cấu hình lại tuần 1–2 (xem Bug Report Tuần 1-3) |
| Postman collection lệch với API thực tế | Trung bình | Đồng bộ Postman + API contract mỗi khi route thay đổi (xem mục 7) |
| Thiếu tài liệu kiểm thử dồn tuần cuối | Cao | Cập nhật test case/bug report mỗi tuần, tuần 15 chỉ tổng hợp |

## 7. Quy trình đồng bộ Postman/API contract

1. Khi BE1/BE2/BE3 thêm hoặc đổi route, cập nhật
   `docs/check-list/TC_BE4_Tuan2_API_Contract_v1.md`.
2. BE4 cập nhật request tương ứng trong
   `tests/Postman/Homi-Backend-v1.postman_collection.json`.
3. Chạy lại `newman run tests/Postman/Homi-Backend-v1.postman_collection.json
   -e tests/Postman/Homi-Local.postman_environment.json` để xác nhận pass.
4. Nếu fail, tạo Bug Report theo mẫu (`docs/bug-report-template.md`).

## 8. Cách chạy test

```bash
# Toàn bộ test Pest/PHPUnit
php artisan test

# Test theo nhóm
php artisan test --filter=Auth
php artisan test tests/Feature/DatabaseSeedTest.php

# Postman/Newman (cần server đang chạy: php artisan serve)
php artisan migrate:fresh --seed
newman run tests/Postman/Homi-Backend-v1.postman_collection.json \
  -e tests/Postman/Homi-Local.postman_environment.json
```

## 9. Lịch sử cập nhật

| Tuần | Người cập nhật | Nội dung |
|---|---|---|
| Tuần 1 | BE4 | Tạo Test Plan v1, test framework, health-check, Postman workspace |
| Tuần 2 | BE4 | Bổ sung test migration/seeder, CI checklist, API contract v1 |
| Tuần 3 | BE4 | Bổ sung kiểm thử Auth/RBAC, đồng bộ Postman với API thực tế, bug list sprint Auth |
