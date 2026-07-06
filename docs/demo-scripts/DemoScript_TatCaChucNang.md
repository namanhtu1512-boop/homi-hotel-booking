# Kịch Bản Demo Đầy Đủ — Toàn Bộ Chức Năng Homi

> Bản đầy đủ, đi qua **mọi** chức năng (kể cả 4 tính năng mới nhất: giữ chỗ,
> giá theo mùa/khuyến mãi stack, dịch vụ thêm, bản đồ, hóa đơn nội bộ, đặt
> đoàn/nhóm, lễ tân & buồng phòng). Bản rút gọn 7-10 phút để bảo vệ xem
> [`DemoScript_Final_Tuan16.md`](DemoScript_Final_Tuan16.md). Đã tự chạy thử
> toàn bộ luồng dưới đây qua server thật (curl) trước khi viết — không phải
> suy đoán từ code.

## Chuẩn bị

```bash
php artisan migrate:fresh --seed   # reset về dữ liệu mẫu sạch
php artisan serve                  # hoặc dùng virtual host Laragon
npm run dev                        # tùy chọn, hot-reload CSS/JS
```

Mở `http://localhost:8000` (hoặc domain Laragon của bạn).

## Tài khoản demo

| Role | Email | Mật khẩu | Vào tại |
|---|---|---|---|
| Admin | `admin@homi.test` | `123456` | `/admin/login` |
| Staff | `staff@homi.test` | `123456` | `/admin/login` (tự chuyển đúng khu vực) |
| Customer | `customer@homi.test` | `123456` | `/customer/login` |
| Customer phụ | `user@gmail.com` | `123456` | test không xem được đơn người khác |
| Customer khóa | `locked@homi.test` | `123456` | test tài khoản bị khóa |

Dữ liệu mẫu sau khi seed: 5 loại phòng (Standard/Superior/Deluxe/Family/Suite),
**46 phòng vật lý** đã đánh số theo tầng (`101-115` Standard, `201-212`
Superior...), 4 dịch vụ thêm, vài mã khuyến mãi, vài đơn đặt phòng mẫu.

---

## PHẦN 1 — Khu vực công khai (không cần đăng nhập)

| Bước | Việc làm | Chú ý |
|---|---|---|
| 1.1 | Mở `/` | Có **bản đồ nhúng Google Maps** + nút "Chỉ đường" ngay dưới phần giới thiệu khách sạn (mới thêm — không cần API key). |
| 1.2 | `/rooms` — lọc theo giá/sức chứa/từ khóa | Chỉ hiện phòng `active`. |
| 1.3 | Vào 1 phòng, nhập ngày → "Kiểm tra phòng trống" | Số phòng trống tính real-time từ booking đang giữ (pending/confirmed/checked_in) **+ các hold tạm thời của session khác** (mới). |
| 1.4 | `/promotions`, `/news`, `/about` | Trang tĩnh, không đổi. |
| 1.5 | `/contact` | Có bản đồ nhúng giống trang chủ. |
| 1.6 | **`/group-bookings`** (mới) | Điền form đặt đoàn: tên liên hệ, email, **số khách tối thiểu 5**, ngày dự kiến, tick chọn loại phòng quan tâm → gửi. Đây là form **xin báo giá thủ công**, không tự tính giá — admin sẽ liên hệ sau. |

---

## PHẦN 2 — Khu vực khách hàng (`/customer/*`)

Đăng nhập `customer@homi.test` / `123456` tại `/customer/login`.

| Bước | Việc làm | Chú ý |
|---|---|---|
| 2.1 | `/customer/bookings/create`, chọn phòng + ngày → "Kiểm tra phòng trống" | **Banner đếm ngược 15 phút xuất hiện** (mới) — phòng vừa kiểm tra được giữ tạm cho đúng session này, người khác kiểm tra cùng phòng/ngày sẽ thấy availability giảm đi. |
| 2.2 | Trong form, tick chọn **"Dịch vụ thêm"** (VD: Ăn sáng buffet, số lượng 2) | Mới — giá tạm tính JS tự cộng thêm dịch vụ. |
| 2.3 | Nhập nhiều mã khuyến mãi cách nhau dấu phẩy vào ô "Mã giảm giá" (nếu có ≥2 mã cùng `stackable=true`) | Mới — xem Phần 3.7 để tạo mã stackable trước. |
| 2.4 | Bấm "Xác nhận đặt phòng" | Booking tạo thành công, hold của session này tự giải phóng ngay. |
| 2.5 | `/customer/bookings` → xem đơn vừa tạo | Thấy dòng phòng + dòng dịch vụ + (nếu có) từng mã khuyến mãi đã áp riêng biệt. |
| 2.6 | Vào chi tiết đơn → **"Xem/In hóa đơn"** (nếu đã thanh toán) hoặc trực tiếp `/customer/bookings/{id}/invoice` | Mới — trang hóa đơn nội bộ riêng, có nút in, ghi rõ "không phải hóa đơn điện tử hợp lệ theo luật thuế". |
| 2.7 | Hủy 1 đơn pending | Availability cộng trả lại ngay. |
| 2.8 | `/customer/wishlist`, `/customer/reviews/create` (sau khi có đơn completed), `/customer/profile` | Không đổi so với trước. |

---

## PHẦN 3 — Khu vực quản trị (`/admin/*`)

Đăng nhập `admin@homi.test` / `123456` tại `/admin/login`.

### 3.1 Tổng quan & dữ liệu gốc
- `/admin/dashboard` — số liệu đơn/doanh thu/tỷ lệ hủy/lấp đầy.
- `/admin/hotel-info/edit` — **thêm 2 field mới**: vĩ độ/kinh độ (lấy từ URL Google Maps) và phụ thu cuối tuần (%) / phụ thu trẻ em (đ/đêm) — cuộn xuống phần giữa form.
- `/admin/room-types` — CRUD loại phòng như cũ.

### 3.2 Phòng vật lý (mới)
- `/admin/rooms` — danh sách 46 phòng đã seed, lọc theo loại phòng, đổi
  trạng thái dọn phòng (Sạch/Cần dọn/Đã kiểm tra/Bảo trì) ngay bằng dropdown
  (auto-submit).
- `/admin/rooms/create` — tạo thêm 1 phòng mới, chọn loại phòng + số phòng.
- Sửa/xóa phòng qua nút trong danh sách.

### 3.3 Đơn đặt phòng + luồng lễ tân đầy đủ (mới)
1. `/admin/bookings` → mở 1 đơn `pending` → **"Xác nhận đơn"**.
2. Sau khi confirmed, nút **"Check-in"** xuất hiện → vào form, với mỗi dòng
   phòng trong đơn, tick đúng số lượng phòng trống hiển thị (VD 1 phòng
   Standard → chọn đúng 1 trong các phòng 101-115 đang rảnh) → "Xác nhận
   check-in". Trạng thái đơn chuyển **"Đang lưu trú"**, số phòng đã gán hiện
   ngay trong bảng phòng của trang chi tiết đơn.
3. Nút **"Check-out"** xuất hiện → bấm → trạng thái chuyển **"Đã trả
   phòng"**, và phòng vừa gán tự động chuyển sang **"Cần dọn"** (kiểm tra lại
   ở `/admin/rooms`).
4. Nút **"Đánh dấu hoàn thành"** giờ vẫn bấm được (từ trạng thái "Đã trả
   phòng") → chuyển "Hoàn thành". (Luồng rút gọn cũ — xác nhận xong hoàn
   thành luôn, không qua check-in/out — vẫn hoạt động song song, không bị
   phá vỡ.)
5. Trên trang chi tiết đơn, nút **"Xem hóa đơn"** mở trang hóa đơn nội bộ.

### 3.4 Khách hàng & tài khoản
- `/admin/customers`, `/admin/users` — không đổi.

### 3.5 Giá theo mùa (mới)
- `/admin/seasonal-rates` → "+ Tạo đợt giá" → chọn "Tất cả loại phòng" hoặc
  1 loại cụ thể, khoảng ngày, loại điều chỉnh (% hoặc số tiền cố định/đêm).
  Hệ thống chặn tạo trùng khoảng ngày cùng phạm vi.
- Đặt phòng trong khoảng ngày đó (Phần 2) để thấy giá đêm thay đổi trong
  breakdown ở trang chi tiết đơn.
- Phụ thu cuối tuần (đêm thứ 6/7) áp dụng tự động nếu đã bật ở 3.1.

### 3.6 Dịch vụ thêm (mới)
- `/admin/services` — CRUD 4 dịch vụ mẫu (Ăn sáng, đưa đón sân bay, trả
  phòng muộn, giường phụ) — có soft-delete + khôi phục.

### 3.7 Khuyến mãi stack (đã nâng cấp)
- `/admin/promotions` → sửa 1 mã, tick **"Cho phép dùng chung với mã
  khác"** → lưu. Tạo/tick thêm 1 mã stackable thứ 2 → dùng cả 2 mã cùng lúc ở
  Phần 2.3 để thấy giảm giá tính tuần tự trên phần còn lại (không cộng %
  trực tiếp).

### 3.8 Đặt đoàn/nhóm (mới)
- `/admin/group-bookings` — xem yêu cầu vừa gửi ở Phần 1.6, bấm "Đánh dấu đã
  liên hệ" sau khi gọi điện báo giá cho khách (thao tác thủ công, không tính
  giá tự động).

### 3.9 Nội dung khác
- `/admin/banners`, `/admin/reviews`, `/admin/news`, `/admin/contact-messages`,
  `/admin/database` — không đổi.

---

## PHẦN 4 — Khu vực nhân viên (`/staff/*`)

Đăng nhập `staff@homi.test` / `123456` tại `/admin/login` (tự chuyển hướng
đúng khu vực staff).

- `/staff/dashboard`, `/staff/hotel-info`, `/staff/room-types` — không đổi.
- **`/staff/rooms`** (mới) — staff chỉ đổi được trạng thái dọn phòng, **không
  tạo/sửa/xóa** được phòng (khác admin).
- **`/staff/bookings/{id}/check-in`, `/check-out`** (mới) — y hệt luồng admin
  ở Phần 3.3, staff xử lý được toàn bộ check-in/check-out/complete/cancel,
  chỉ không có quyền xóa loại phòng hay xem `/admin/database`.

---

## Known Limitations còn lại

Xem [`Known_Limitations_Tuan16.md`](../check-list/Known_Limitations_Tuan16.md).
Sau đợt này, các mục đã thu hẹp: ~~hold~~, ~~giá mùa~~, ~~stack khuyến mãi~~,
~~dịch vụ~~, ~~bản đồ~~, ~~hóa đơn~~, ~~đặt đoàn~~, ~~CHECKED_IN/CHECKED_OUT
chưa dùng~~ — đều đã giải quyết. Còn lại chủ yếu: thanh toán ví điện tử
thật (MoMo/ZaloPay), đa ngôn ngữ, xác nhận qua Zalo/SMS, đồng bộ đa kênh,
CI/CD + giám sát hạ tầng thật — đều cần tài khoản/dịch vụ bên ngoài thật,
nằm ngoài phạm vi có thể tự làm.
