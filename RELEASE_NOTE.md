# Release Note — Homi Hotel Booking (RC1, Sprint 8 / Tuần 16)

**Ngày:** 2026-07-06
**Trạng thái:** Release Candidate — sẵn sàng nộp/bảo vệ, chạy ổn định local
(chưa deploy staging thật, xem lý do ở mục Known Limitations).

## Tóm tắt

Website đặt phòng và quản lý cho **1 khách sạn duy nhất** (Homi), xây bằng
Laravel 13 Blade monolith. Đủ 3 khu vực: public (xem/tìm phòng), customer
(đặt phòng, quản lý đơn), admin/staff (quản trị toàn bộ nghiệp vụ). Toàn bộ
8 sprint theo kế hoạch gốc đã hoàn thành.

## Phạm vi đã hoàn thành

- **Auth/RBAC**: đăng ký, đăng nhập/đăng xuất, đổi email/mật khẩu, 3 role
  (customer/staff/admin), rate-limit chống brute-force.
- **Hotel & Room**: thông tin khách sạn singleton, CRUD loại phòng/giá/tồn
  kho, ảnh thật, tiện ích, tìm kiếm/lọc công khai.
- **Booking core**: kiểm tra phòng trống theo overlap ngày, tạo đơn bằng
  transaction có khóa row chống race condition, khách xem/hủy đơn, admin
  xác nhận/hủy/hoàn thành với state machine đầy đủ + log lịch sử.
- **Payment**: mô phỏng đầy đủ (pay-at-hotel, online demo, báo chuyển
  khoản, đặt cọc 30%), admin cập nhật trạng thái, tự động hoàn tiền khi hủy
  đơn đã thanh toán.
- **Admin mở rộng**: dashboard thống kê (đơn, doanh thu, tỷ lệ hủy/lấp đầy),
  quản lý khách hàng (tách khỏi quản lý tài khoản), audit log thao tác
  admin, quản lý khuyến mãi/đánh giá/tin tức/banner/liên hệ.
- **API JSON `/api/v1/*`**: hoàn thiện đầy đủ (không còn stub), dùng
  Sanctum — vai trò phụ, xem README.

## Số liệu kiểm thử

**538/538 test pass**, 1276 assertion, 0 failed — chi tiết breakdown theo
module xem [`docs/check-list/TestReport_Final_Tuan15.md`](docs/check-list/TestReport_Final_Tuan15.md).

> Lưu ý cho lần chạy lại sau này: `tests/Unit/Services/AvailabilityServiceTest.php`
> trước đây dùng ngày hardcode tuyệt đối (`2026-07-05`...) — khi ngày thật
> trôi qua mốc đó, test tự fail dù code không đổi (`DateRangeService` chặn
> check_in trước hôm nay). Đã sửa sang ngày tương đối (`now()->addDays(...)`)
> ở Tuần 16 nên test suite không còn "tự hết hạn" theo thời gian.

## Bảo mật đã siết trong quá trình review

Chặn lộ password hash ở trang xem DB nội bộ, chặn brute-force login/đăng
ký/liên hệ, chặn SVG upload (nguy cơ XSS), fix race condition có thể
overbook phòng, fix logic tính giảm giá sai khi percent=0 — chi tiết đầy đủ
xem [`docs/check-list/Bug_Report_Sprint7_Tuan14.md`](docs/check-list/Bug_Report_Sprint7_Tuan14.md).

## Known limitations

Xem đầy đủ tại [`docs/check-list/Known_Limitations_Tuan16.md`](docs/check-list/Known_Limitations_Tuan16.md) —
tóm tắt: thanh toán/email chỉ mô phỏng, không có module dịch vụ riêng, chưa
deploy staging thật (dùng phương án local ổn định).

## Cách chạy

```bash
git clone <repo-url> && cd homi-hotel-booking
composer run setup
php artisan serve
```

Chi tiết đầy đủ + tài khoản demo: [`README.md`](README.md). Kịch bản demo:
[`docs/demo-scripts/DemoScript_Final_Tuan16.md`](docs/demo-scripts/DemoScript_Final_Tuan16.md).
Backup/restore DB: [`docs/check-list/DB_Checklist_Tuan16.md`](docs/check-list/DB_Checklist_Tuan16.md).

## Phương án dự phòng nếu demo trực tiếp gặp sự cố

Chạy local trên máy đã setup sẵn (không phụ thuộc mạng/staging) — đã xác
nhận là phương án hợp lệ theo tiêu chí nghiệm thu của kế hoạch gốc. Chi
tiết: [`docs/check-list/Staging_Checklist_Tuan14.md`](docs/check-list/Staging_Checklist_Tuan14.md) mục 9.
