# Staging / Local Deploy Checklist — Tuần 14 (RC1)

**Mục đích:** Đảm bảo bất kỳ ai (bạn cùng nhóm, hoặc thầy hướng dẫn) clone repo về đều setup chạy được ngay, không phải tự dò lỗi môi trường. Đây là RC1 (Release Candidate 1) — chưa phải bản nộp cuối (Sprint 8), nhưng phải chạy demo được ổn định.

---

## 1. Yêu cầu môi trường

| Phần mềm | Phiên bản tối thiểu | Kiểm tra bằng |
|---|---|---|
| PHP | 8.3 | `php -v` |
| Composer | 2.x | `composer -V` |
| MySQL | 8.0+ (hoặc SQLite cho máy không cài MySQL) | `mysql --version` |
| Node.js | 20+ | `node -v` |

## 2. Cài đặt từ đầu (clone mới)

```bash
git clone <repo-url>
cd homi-hotel-booking
composer run setup
```

`composer run setup` đã làm đủ các bước: `composer install` → copy `.env` → `key:generate` →
**`migrate --seed`** → **`storage:link`** → `npm install` → `npm run build`.

> ⚠️ Trước tuần này, script `setup` thiếu `storage:link` và không seed — đã sửa
> trong `composer.json`. Nếu bạn đã setup từ trước tuần 14, chạy bù 2 lệnh:
> ```bash
> php artisan storage:link
> php artisan db:seed
> ```

## 3. Cấu hình `.env` bắt buộc chỉnh tay (script không tự làm được)

| Biến | Việc cần làm |
|---|---|
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Khớp với MySQL local của bạn (hoặc đổi `DB_CONNECTION=sqlite` + tạo `database/database.sqlite` nếu không có MySQL). |
| `APP_URL` | Khớp với domain Laragon/Herd/valet của bạn (vd `http://homi-hotel-booking.test`). |

## 4. Chạy server

```bash
php artisan serve        # nếu không dùng Laragon/Herd virtual host
npm run dev               # nếu cần hot-reload Vite khi chỉnh CSS/JS
```

## 5. Smoke test sau khi setup — chạy đủ 4 bước này trước khi báo "chạy được"

1. `php artisan test` → phải thấy **496/496 pass** (số này tăng theo thời gian, xem `phpunit.result.cache` hoặc log CI để biết số hiện tại — quan trọng là **0 failed**).
2. Mở `/` — trang chủ load được, thấy banner + phòng nổi bật + tin tức.
3. Đăng nhập cả 3 tài khoản demo (bảng dưới) và xác nhận redirect đúng khu vực (`/customer/dashboard`, `/admin/dashboard`, `/staff/dashboard`).
4. Mở `/admin/room-types` → ảnh phòng phải hiển thị được (không phải icon ảnh vỡ). Nếu ảnh vỡ → `storage:link` chưa chạy hoặc chạy sai thư mục.

## 6. Tài khoản demo (sau khi seed)

| Role | Email | Mật khẩu | Khu vực đăng nhập |
|---|---|---|---|
| Admin | `admin@homi.test` | `123456` | `/admin/login` |
| Staff | `staff@homi.test` | `123456` | `/admin/login` (dùng chung form, redirect theo role) |
| Customer | `customer@homi.test` | `123456` | `/customer/login` |
| Customer phụ | `user@gmail.com` | `123456` | `/customer/login` — dùng để test không xem được đơn người khác |
| Customer đã khóa | `locked@homi.test` | `123456` | Phải bị chặn đăng nhập — dùng để test tài khoản khóa |

Chi tiết dữ liệu mẫu có sẵn (mã đơn, mã khuyến mãi...) xem [`docs/demo-scripts/DemoScript_Tuan10-13.md`](../demo-scripts/DemoScript_Tuan10-13.md).

## 7. Security checklist trước khi đưa cho người ngoài xem (staging thật, không phải máy cá nhân)

- [ ] `.env` **không** được commit lên git (đã có trong `.gitignore` — chỉ cần không dùng `git add -f`).
- [ ] `APP_DEBUG=false` trên môi trường staging/production thật (máy demo cá nhân cho thầy xem qua localhost thì giữ `true` cũng được, không bắt buộc).
- [ ] `APP_ENV` không để `local` nếu deploy lên server công khai.
- [ ] Đã chạy `php artisan test` full pass trước khi build (không có regression) — kết quả tại BUG-S7 report (`Bug_Report_Sprint7_Tuan14.md`).
- [ ] `/admin/database` (trang xem nhanh DB) đã được vá không lộ password hash/remember_token — xem BUG-S7-03. Vẫn nên coi trang này là công cụ debug nội bộ, không mở public.
- [ ] Không có secret (API key thật, mật khẩu DB thật) nào bị hardcode trong code — đã grep `app/` không thấy.

## 8. Vấn đề đã biết (không phải bug môi trường, ghi để không tốn thời gian debug lại)

| Vấn đề | Giải thích |
|---|---|
| Thanh toán chỉ là mô phỏng | Chưa nối VNPay/Momo thật — `PaymentMethod::ONLINE_DEMO` sinh mã giao dịch giả `DEMO-xxxxx`. Đúng theo phạm vi đồ án ("thanh toán mô phỏng"). |
| Email không gửi thật | `MAIL_MAILER=log` — mọi email (nếu có) chỉ ghi vào `storage/logs/laravel.log`, không gửi thật. Không có tính năng gửi email nào đang được dùng ở luồng chính hiện tại. |
| README.md mô tả dự án là "Backend API" | Đã lỗi thời — dự án hiện là Blade monolith, README chưa cập nhật theo. Việc viết lại README đầy đủ (hướng dẫn cài đặt, tài khoản demo, cấu trúc thư mục đúng thực tế) là nhiệm vụ Tuần 15 (BE1), chưa làm ở tuần này để tránh trùng phạm vi. |
| Có cả `/api/v1/*` (REST API) và route Blade | Kiến trúc song song còn sót từ giai đoạn đầu dự án — xem mục "Không sửa trong sprint này" ở `Bug_Report_Sprint7_Tuan14.md`. Không ảnh hưởng demo Blade chính. |

## 9. Nếu deploy lên staging thật (không chỉ chạy local)

- [ ] Chọn phương án: shared hosting hỗ trợ PHP 8.3 + MySQL (vd 000webhost/Hostinger cho demo tạm), hoặc VPS tự cấu hình Nginx + PHP-FPM.
- [ ] Chạy `npm run build` trước khi upload (không chạy `npm run dev` trên production).
- [ ] Set quyền ghi cho `storage/` và `bootstrap/cache/` (`chmod -R 775` trên Linux).
- [ ] Chạy `php artisan config:cache route:cache view:cache` sau khi deploy để tăng tốc (nhớ chạy lại mỗi khi đổi `.env`/route).
- [ ] Nếu không có staging thật kịp trước ngày báo cáo, phương án dự phòng hợp lệ theo kế hoạch là **"chạy local ổn định + hướng dẫn cài đặt rõ ràng cho thầy"** (đã đủ theo tiêu chí nghiệm thu Tuần 14: *"Có link staging hoặc phương án chạy local ổn định cho thầy kiểm tra"*).
