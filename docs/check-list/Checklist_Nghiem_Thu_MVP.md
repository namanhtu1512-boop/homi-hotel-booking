# CHECKLIST NGHIỆM THU MVP – HOMI BACKEND

**Phụ trách:** BE4 (chính), BE1 review
**Phiên bản:** v1 – Tuần 1 (cập nhật dần theo các mốc nghiệm thu trong kế hoạch 16 tuần)

> Checklist này tổng hợp điều kiện nghiệm thu của MVP Homi (mục 7 của kế hoạch
> 16 tuần) thành các đầu việc kiểm tra được, để BE4 và cả nhóm dùng làm tiêu
> chí "Done" ở từng mốc. Đánh dấu `[x]` khi đã kiểm tra và có minh chứng
> (Pest pass hoặc Postman pass) trong `docs/test-evidence/`.

## Mốc sau Tuần 1 — Nền tảng

- [x] Repo Laravel 13 chạy được, `php artisan serve` thành công.
- [x] `.env` cấu hình đúng, `php artisan key:generate` chạy được.
- [x] Test framework (Pest) chạy được — `php artisan test` không lỗi cấu hình.
- [x] `/api/health` trả về `success`, `message`, `data.app/env/time/database`.
- [x] Postman workspace v1 có collection + environment local.
- [x] Test Plan tổng quát, Bug Report template, Checklist nghiệm thu MVP đã có.

## Mốc sau Tuần 2 — Database & CI

- [x] `php artisan migrate:fresh` chạy sạch, đủ bảng domain core
      (users, hotels, room_types, bookings, booking_items, payments, ...).
- [x] `php artisan db:seed` tạo đủ 3 role (customer/staff/admin) + 1 tài
      khoản bị khóa (`status = locked`).
- [x] Quan hệ hotel ↔ room_types ↔ amenities hoạt động sau seed.
- [x] CI (`.github/workflows/laravel-tests.yml`) chạy `php artisan test` khi
      push/PR vào `main`/`develop`.
- [x] API contract v1 tổng hợp từ route list (`docs/check-list/TC_BE4_Tuan2_API_Contract_v1.md`).

## Mốc sau Tuần 3 — Auth & RBAC

- [x] Đăng ký, đăng nhập, đăng xuất, xem/cập nhật hồ sơ hoạt động đúng.
- [x] Phân quyền customer / staff / admin đúng theo route middleware.
- [x] Tài khoản bị khóa không đăng nhập được (403).
- [x] Token bị thu hồi/sai trả 401; thiếu quyền trả 403.
- [x] **Password không bao giờ xuất hiện trong response API** (kiểm tra ở
      `RegisterTest`, `LoginTest`, Postman "Get Me - Customer").
- [x] Bug list sprint Auth đã lập, các bug Critical/High đã sửa và retest
      (xem `docs/bug-reports/`).
- [x] Toàn bộ Postman collection (`Homi-Backend-v1.postman_collection.json`)
      pass 100% assertion với environment local
      (xem `docs/test-evidence/postman-run-tuan3.*`).

## Mốc sau Tuần 4 — Core backend ổn định

- [ ] Exception handler, validation message tiếng Việt, mã lỗi chuẩn.
- [ ] Audit log cơ bản.
- [ ] Toàn bộ test nền tảng (Tuần 1-4) pass trong CI.

## Mốc sau Tuần 6 — Quản lý khách sạn & loại phòng

- [ ] CRUD hotels, room_types có test pass + dữ liệu seed.
- [ ] (Khi hoàn thành) bổ sung bảng `room_type_amenity` vào
      `DatabaseSeedTest` (hiện đang để ngoài phạm vi tuần 1-3, xem ghi chú
      trong file test).

## Mốc sau Tuần 10 — Luồng lõi (quan trọng nhất)

- [ ] Kiểm tra phòng trống đúng với mọi case overlap ngày.
- [ ] Tạo đơn + tính tiền bằng transaction, không đặt trùng phòng.

## Mốc sau Tuần 16 — Bản nộp cuối

- [ ] `migrate:fresh --seed` chạy lại được trên máy người chấm.
- [ ] Test report cuối, release note, known limitations đầy đủ.

---

**Ghi chú:** các mốc Tuần 4 trở đi sẽ được cập nhật dần khi các tuần đó được
thực hiện; checklist này chỉ phản ánh phần BE4 đã hỗ trợ/kiểm tra tới Tuần 3.
