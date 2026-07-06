# Kịch Bản Demo Cuối — Tuần 16 (bản cầm tay khi bảo vệ, 7-10 phút)

> Bản rút gọn để trình bày trước thầy — bản chi tiết để tự tập luyện xem
> [`DemoScript_Tuan10-13.md`](DemoScript_Tuan10-13.md). Tài khoản demo và mã
> đơn/khuyến mãi mẫu xem README hoặc file đó.

Trước khi bắt đầu: `php artisan migrate:fresh --seed`, `php artisan serve`.

| # | Thời điểm | Việc làm | Câu nói kèm theo |
|---|---|---|---|
| 1 | 0:00-0:30 | Mở `/` | "Homi chỉ quản lý 1 khách sạn duy nhất, không phải nền tảng đa khách sạn." |
| 2 | 0:30-1:30 | `/rooms`, lọc theo giá/sức chứa | "Khách xem và lọc phòng active, không đăng nhập vẫn xem được." |
| 3 | 1:30-2:30 | Vào 1 phòng, nhập ngày kiểm tra trống | "Số phòng trống tính trực tiếp từ booking đang giữ chỗ (pending/confirmed/checked_in), không cache." |
| 4 | 2:30-3:30 | Đăng nhập `customer@homi.test`, đặt phòng | "Toàn bộ nằm trong 1 DB transaction, khóa row loại phòng trước khi tính lại availability — chống 2 khách cùng đặt phòng cuối." |
| 5 | 3:30-4:00 | Vào `/customer/bookings`, hủy 1 đơn pending | "Hủy xong, availability cộng trả lại ngay vì không cache." |
| 6 | 4:00-4:30 | Đăng nhập `admin@homi.test` qua `/admin/login` | "Admin/staff dùng chung form nhưng có `login_context` riêng, không lẫn với khách hàng." |
| 7 | 4:30-5:15 | `/admin/dashboard` | "Tổng đơn, tỷ lệ hủy, doanh thu, tỷ lệ lấp đầy — số liệu khớp trực tiếp với `/admin/bookings`." |
| 8 | 5:15-6:15 | `/admin/bookings` → xác nhận 1 đơn pending → đánh dấu paid | "State machine chặn chuyển sai bước, có log ai làm lúc nào (`booking_status_logs`)." |
| 9 | 6:15-7:00 | `/admin/customers` → xem lịch sử đặt phòng 1 khách | "Tách khỏi `/admin/users` — trang riêng cho nghiệp vụ CSKH." |
| 10 | 7:00-7:45 | `/admin/room-types` → sửa giá/số lượng 1 phòng | "Không cho giảm số phòng xuống dưới mức đang có khách đặt trong tương lai." |

## Câu hỏi khó có thể gặp — trả lời ngắn

- **"Sao có cả API lẫn Blade?"** → Tầng API là tàn dư giai đoạn đầu, đã hoàn
  thiện, nhưng sản phẩm chính chấm điểm là Blade.
- **"Chống đặt trùng phòng thế nào khi 2 người bấm cùng lúc?"** →
  `lockForUpdate()` trên RoomType + `SELECT ... FOR UPDATE` luôn đọc dữ liệu
  mới nhất đã commit, tất cả trong 1 `DB::transaction()` — xem
  `app/Services/BookingService.php`.
- **"Có gì chưa hoàn thiện không?"** → Trả lời thẳng, dẫn
  [`Known_Limitations_Tuan16.md`](../check-list/Known_Limitations_Tuan16.md)
  thay vì né tránh — gây thiện cảm hơn là để thầy tự phát hiện.
