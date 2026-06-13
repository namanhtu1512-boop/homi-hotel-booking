# CHECKLIST DATABASE & CI - TUẦN 2 (BE4)

> **Cập nhật Tuần 3:** đã chuyển database test từ MySQL service container sang
> **SQLite in-memory** (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` trong
> `phpunit.xml`) để test chạy nhanh, không cần service ngoài, và để khắc phục
> lỗi `could not find driver (mysql)`. CI workflow tương ứng đã được tạo tại
> `.github/workflows/laravel-tests.yml` (không cần MySQL service).

## A. Checklist Database (chạy sau khi BE2/BE3 push migration)

| # | Hạng mục | Lệnh / cách kiểm tra | Đạt |
|---|---|---|---|
| 1 | Migrate sạch không lỗi | `php artisan migrate:fresh` | [x] |
| 2 | Tất cả bảng domain tồn tại | xem `php artisan migrate:status` hoặc test `DatabaseSeedTest` | [x] |
| 3 | Foreign key đúng (xóa cascade hotel → room_types → ...) | xóa thử 1 hotel test, kiểm tra room_types liên quan | [ ] (sẽ kiểm tra kỹ hơn khi BE2 hoàn thiện CRUD hotels ở Tuần 5-6) |
| 4 | Seed chạy không lỗi | `php artisan migrate:fresh --seed` | [x] |
| 5 | Seed tạo đủ 3 role user (admin/staff/customer) + 1 user bị khóa | query `users` table hoặc chạy `DatabaseSeedTest` | [x] (đã bổ sung `locked@homi.test`, status=`locked` trong `UserSeeder`) |
| 6 | Seed tạo hotels kèm room_types, amenities | kiểm tra quan hệ qua tinker hoặc test | [x] |
| 7 | Enum trạng thái booking/payment đúng với bảng nghiệp vụ đã thống nhất (tuần 2) | review migration `bookings`, `payments` | [ ] (BE3 review chi tiết ở Tuần 9-10) |
| 8 | Không có cột nhạy cảm (password) trả ra ngoài qua model `$hidden` | review `User.php` | [x] (`$hidden = ['password', 'remember_token']`, có test `password_not_in_login_response`) |

## B. Checklist CI (GitHub Actions)

| # | Hạng mục | Đạt |
|---|---|---|
| 1 | File `.github/workflows/laravel-tests.yml` đã thêm vào repo | [x] |
| 2 | CI tự chạy khi push/PR vào `main`/`develop` | [x] |
| 3 | CI cài đặt PHP đúng version (>= 8.2, dùng 8.3) | [x] |
| 4 | CI tạo database test | [x] — dùng SQLite in-memory, không cần service container ngoài |
| 5 | CI chạy `migrate:fresh --seed` thành công (sanity check) | [x] |
| 6 | CI chạy toàn bộ test suite (Pest/PHPUnit), build fail nếu có test fail | [x] |
| 7 | Thông báo CI fail được cả nhóm nhìn thấy (Github status check trên PR) | [x] (mặc định GitHub Actions hiển thị status check trên PR) |

> **Lưu ý composer:** `vendor/` đã có sẵn `pestphp/pest` 4.7.2 và Pest chạy
> được (`tests/Pest.php` đã được bổ sung ở Tuần 1-3 để fix lỗi
> "Did you forget to use the pest()->extend() function?"). Tuy nhiên
> `composer.json`/`composer.lock` hiện chưa khai báo `pestphp/pest` trong
> `require-dev` (lệch với `vendor/`). Khi có kết nối mạng tới Packagist, chạy:
> `composer require pestphp/pest:^4.7 --dev` để đồng bộ lại `composer.lock`.
> Xem chi tiết trong Bug Report Tuần 1-3 (`docs/bug-reports/`).

## C. Quy trình khi CI fail

1. BE4 kiểm tra log CI, xác định module/test nào fail.
2. Tạo bug report (theo mẫu tuần 1) với mức độ phù hợp (thường High/Critical vì chặn merge).
3. Gán cho người phụ trách module (BE1/BE2/BE3) sửa.
4. Sau khi fix và push lại, CI tự chạy lại - BE4 xác nhận pass rồi mới đóng bug.