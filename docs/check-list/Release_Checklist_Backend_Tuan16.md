# Release Checklist Backend — Tuần 16 (BE1)

## Checklist trước khi nộp

- [x] `README.md` mô tả đúng thực tế (Blade monolith, không phải "Backend
      API") — cập nhật ở Tuần 15.
- [x] `.env.example` đủ biến cần thiết, `APP_NAME=Homi`, `APP_LOCALE=vi`
      (trước đây để `Laravel`/`en` — đã sửa Tuần 16, kích hoạt luôn
      `lang/vi/{auth,passwords,validation}.php` vốn có sẵn nhưng chưa từng
      được dùng).
- [x] Tài khoản demo đủ 5 tài khoản (admin/staff/customer/customer phụ/
      customer khóa), có trong `README.md` và `docs/check-list/Staging_Checklist_Tuan14.md`.
- [x] Danh sách route chính đã xuất — xem
      [`Route_List_Tuan16.md`](Route_List_Tuan16.md) (140 route, tổ chức
      theo khu vực Public/Customer/Admin/Staff/API).
- [x] `composer run setup` chạy đủ từ đầu (`migrate --seed` + `storage:link`
      — 2 bước từng thiếu, đã sửa ở Tuần 14).

## Q&A dự kiến khi bảo vệ (kiến trúc backend / Auth / RBAC)

**Vì sao tách `/customer`, `/admin`, `/staff` thành 3 khu vực riêng?**
Phân quyền rõ theo route-group middleware (`role:customer|admin|staff`),
tránh nhầm lẫn giao diện, và mỗi khu vực có layout/khả năng riêng (staff
không có quyền xóa loại phòng hay xem `/admin/database`).

**`RoleMiddleware` hoạt động thế nào?** 1 middleware dùng chung cho cả
`role:admin`, `role:staff`, `role:customer` (tham số động) — kiểm tra thứ tự:
chưa đăng nhập → redirect đúng trang login; tài khoản `locked` → chặn 403;
role không khớp → redirect về đúng dashboard của role đó (không phải 403
trắng); riêng route admin/staff còn kiểm tra thêm `session('login_context')
=== 'admin'` để tài khoản đăng nhập nhầm qua form khách hàng không lọt vào
được (xem `app/Http/Middleware/RoleMiddleware.php`).

**Vì sao có cả API JSON lẫn Blade?** Tầng API (`/api/v1/*`) là tàn dư từ
giai đoạn đầu dự án trước khi nhóm chuyển hẳn sang Blade monolith theo đúng
định hướng đề tài (1 khách sạn, không cần app di động/SPA riêng). Đã hoàn
thiện thay vì để dở dang, nhưng sản phẩm chính để chấm là Blade.

**Chống đặt trùng phòng (race condition) xử lý ra sao?** Overlap theo điều
kiện `check_in < X.check_out AND check_out > X.check_in`, cộng
`SELECT ... FOR UPDATE` khóa row RoomType bên trong `DB::transaction()`
trước khi tính lại availability — xem `app/Services/AvailabilityService.php`
và `app/Services/BookingService.php`.

**Mật khẩu/token có an toàn không?** Mật khẩu hash bằng bcrypt
(`Hash::make`), không bao giờ trả ra JSON/view (`$hidden` trên User model).
API dùng Sanctum personal access token. Có rate-limit 5 lần/phút chống
brute-force ở form đăng nhập/đăng ký/liên hệ (thêm ở Tuần 15).
