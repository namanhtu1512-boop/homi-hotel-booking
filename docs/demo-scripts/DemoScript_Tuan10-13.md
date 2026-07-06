# Demo Script — Tuần 10, 11, 12, 13 (Sprint 5 → Sprint 7)

> Dùng để demo nội bộ / sprint review với thầy hướng dẫn. Đi đúng thứ tự các bước để câu chuyện nghiệp vụ liền mạch: đặt phòng → khách tự quản lý đơn → admin xử lý đơn/thanh toán → quản lý khách hàng + dashboard + đánh giá.

## 0. Chuẩn bị trước khi demo

```bash
php artisan migrate:fresh --seed
php artisan serve
npm run dev   # nếu cần build asset
```

**Tài khoản demo** (mật khẩu chung: `123456`):

| Vai trò | Email | Ghi chú |
|---|---|---|
| Customer | `customer@homi.test` | Tài khoản chính dùng xuyên suốt demo |
| Customer phụ | `user@gmail.com` | Dùng để test không xem được đơn của người khác |
| Customer đã khóa | `locked@homi.test` | Dùng để test tài khoản khóa không đăng nhập được |
| Staff | `staff@homi.test` | Khu vực `/staff/*` |
| Admin | `admin@homi.test` | Khu vực `/admin/*` |

**Dữ liệu mẫu có sẵn sau khi seed** (đỡ phải tạo tay khi demo gấp):

| Mã đơn | Trạng thái | Phòng | Ghi chú |
|---|---|---|---|
| `HOMI-DEMO-PENDING` | pending | Standard | Dùng để demo admin xác nhận đơn |
| `HOMI-DEMO-CONFIRMED` | confirmed, đã paid | Superior | Dùng để demo hoàn thành đơn + viết đánh giá |
| `HOMI-DEMO-CANCELLED` | cancelled, đã refund | Standard | Dùng để đối chiếu lịch sử |
| `HOMI-DEMO-OVERLAP-1/2/3` | confirmed | Suite (tổng 3 phòng) | 3 đơn giao nhau 18-25/07/2026 → **hết phòng đúng khoảng này**, dùng để demo availability |

Mã khuyến mãi hợp lệ: `HOMISUMMER` (giảm 15%), `WELCOME100K` (giảm 100k), `EARLYBIRD20` (giảm 20%). Mã `TETHOMI2026` đã hết hạn — dùng để demo "mã không hợp lệ".

---

## Tuần 10 — Tạo booking, tính tiền, transaction (Sprint 5)

**Sprint goal:** khách chọn ngày → kiểm tra trống → tạo booking → payment pending.

1. Vào `/rooms`, mở chi tiết **Phòng Suite**.
2. Nhập ngày `20/07/2026 – 22/07/2026`, bấm kiểm tra phòng trống → **hết phòng** (rơi vào khoảng 3 đơn overlap đã seed, Suite chỉ có 3 phòng).
3. Đổi sang ngày trống, ví dụ `10/09/2026 – 12/09/2026` → còn phòng, bấm **Đặt phòng**.
4. Đăng nhập bằng `customer@homi.test` nếu chưa đăng nhập (route customer bị chặn với guest → chứng minh route được bảo vệ).
5. Điền form đặt phòng: số lượng, số người lớn/trẻ em, thông tin liên hệ. Nhập mã khuyến mãi `HOMISUMMER` → tổng tiền giảm 15%.
6. Thử nhập số khách vượt sức chứa phòng → hệ thống chặn với thông báo rõ ràng (validate theo từng loại phòng, không gộp chung).
7. Bỏ số khách vượt mức, submit đơn thành công → thấy trang xác nhận có **mã đơn** (`HOMI-...`), trạng thái `pending`, payment `unpaid`.
8. (Điểm nhấn kỹ thuật) Nhắc lại: toàn bộ bước 5-7 chạy trong 1 `DB::transaction`, availability được re-check ngay trước khi insert để chống race condition khi 2 khách đặt cùng lúc.

---

## Tuần 11 — Customer quản lý booking (Sprint 6)

**Sprint goal:** khách xem/hủy đơn của mình; availability cập nhật đúng sau khi hủy.

1. Vào `/customer/bookings` (đang đăng nhập `customer@homi.test`) → thấy danh sách gồm đơn seed (`HOMI-DEMO-PENDING`, `HOMI-DEMO-CONFIRMED`, `HOMI-DEMO-CANCELLED`) và đơn vừa tạo ở Tuần 10.
2. Mở chi tiết `HOMI-DEMO-PENDING` → bấm **Hủy đơn** → status chuyển `cancelled`.
3. Quay lại `/rooms/{Standard}`, kiểm tra trống đúng ngày của đơn vừa hủy (10/07-12/07/2026) → số phòng trống **tăng lại 1** so với trước khi hủy (chứng minh availability tính lại tự động, không cache).
4. Đăng xuất, đăng nhập bằng `user@gmail.com`, gõ thẳng URL chi tiết đơn của `customer@homi.test` (ví dụ `/customer/bookings/{id}` với id đơn vừa xem) → bị chặn, không xem được đơn của người khác.
5. Thử hủy đơn `HOMI-DEMO-CONFIRMED` sau khi đã set ngày check-in về quá khứ (hoặc test case sẵn có) → minh họa rule "chỉ hủy được trước ngày nhận phòng".

---

## Tuần 12 — Admin quản lý booking/payment (Sprint 6)

**Sprint goal:** admin xem đơn → xác nhận → thanh toán.

1. Đăng nhập `/admin/login` bằng `admin@homi.test`.
2. Vào `/admin/bookings` → demo bộ lọc: theo trạng thái, thanh toán, loại phòng, tên khách, khoảng ngày đặt, khoảng ngày check-in.
3. Mở đơn `HOMI-DEMO-PENDING` (nếu chưa hủy ở bước Tuần 11, dùng đơn khác đang pending) → bấm **Xác nhận** → status `pending → confirmed`.
4. Thử bấm xác nhận lần 2 → bị chặn ("chỉ xác nhận được đơn đang chờ") → minh họa state machine.
5. Đánh dấu **đã thanh toán** cho đơn vừa xác nhận → xem lịch sử thanh toán ghi rõ người thực hiện + thời gian (`payment_status_logs`).
6. Hủy đơn `HOMI-DEMO-CONFIRMED` (đã paid) → hệ thống **tự động refund** payment, xem lại trong lịch sử trạng thái.
7. Vào `/admin/payments` → lọc theo trạng thái/mã đơn/tên khách, thử đánh dấu refund trực tiếp từ `unpaid` → bị chặn (chỉ `paid → refunded`).
8. Đăng xuất, đăng nhập `staff@homi.test` → vào `/staff/bookings`, `/staff/payments`, thao tác xác nhận/thanh toán tương tự — nhưng sidebar **không có** Users/Database/xóa loại phòng (khu vực riêng, quyền hẹp hơn admin).
9. Đăng nhập lại bằng `customer@homi.test`, gõ thẳng `/admin/bookings` → bị redirect về `/customer/dashboard` (không phải 403 trắng — RoleMiddleware điều hướng theo đúng vai trò).

---

## Tuần 13 — Khách hàng, dashboard, đánh giá cơ bản (Sprint 7)

**Sprint goal:** quản lý khách hàng tách biệt tài khoản, dashboard đối chiếu đúng DB, đánh giá hoạt động đúng điều kiện.

1. Với `admin@homi.test`, vào **Khách hàng** (`/admin/customers`, menu riêng với **Người dùng** `/admin/users`).
2. Tìm kiếm theo tên/email/SĐT, lọc theo trạng thái khóa → thấy `Locked Customer Demo` ở trạng thái "Đã khóa".
3. Mở chi tiết `Customer Demo` → thấy **lịch sử đặt phòng đầy đủ** (các mã `HOMI-DEMO-...`), không lẫn đơn của khách khác.
4. Bấm **Khóa tài khoản** ngay trên trang chi tiết → đăng xuất → thử đăng nhập lại `customer@homi.test` → bị chặn "tài khoản đã bị khóa". Mở khóa lại để tiếp tục demo.
5. Vào `/admin/promotions` → xem 4 khuyến mãi mẫu, chỉ ra mã đã hết hạn (`TETHOMI2026`, status inactive) không hiển thị ở trang public `/`; nếu khách cố nhập mã này lúc đặt phòng sẽ bị báo "mã giảm giá không hợp lệ hoặc đã hết hạn".
6. Mở `HOMI-DEMO-CONFIRMED` trong `/admin/bookings`, bấm **Hoàn thành đơn** (`confirmed → completed`).
7. Đăng nhập `customer@homi.test`, vào `/customer/reviews/create` → thấy **Phòng Superior** xuất hiện (vì đơn vừa completed) → chọn 5 sao, viết nhận xét, gửi.
8. Vào trang công khai `/rooms/{Phòng Superior}` → thấy đánh giá mới + **điểm trung bình** cập nhật.
9. Quay lại `admin@homi.test`, vào `/admin/dashboard` → chỉ đủ các số liệu: trạng thái khách sạn, loại phòng active, **tổng khách hàng**, tổng đơn, pending, confirmed, cancelled, **tỷ lệ hủy (%)**, tỷ lệ lấp đầy hôm nay, và biểu đồ doanh thu 6 tháng gần nhất.
10. Đối chiếu nhanh: mở `/admin/bookings?status=cancelled` đếm số dòng, so với số "Đơn đã hủy" trên dashboard → khớp nhau.

---

## Câu hỏi/kịch bản dự phòng khi bị hỏi thêm

- **"Sao không đặt trùng phòng khi 2 khách bấm cùng lúc?"** → chỉ vào `AvailabilityService::canBook()` được gọi lại bên trong `DB::transaction` của `BookingService::create()`, không chỉ tin vào kết quả kiểm tra trống hiển thị trên form trước đó.
- **"Hủy đơn xong tiền có mất không?"** → tiền cọc (`deposit_paid`) không tự hoàn khi hủy (chính sách giữ chỗ), chỉ khoản đã trả đủ (`paid`) mới tự động refund — xem `PaymentStatus::canRefund()`.
- **"Vì sao tách `/admin/customers` khỏi `/admin/users`?"** → `/admin/users` quản lý tài khoản nói chung (cả admin/staff/customer, chỉ khóa/mở), `/admin/customers` là màn nghiệp vụ riêng cho CSKH — tìm khách và xem lịch sử đặt phòng nhanh mà không lẫn tài khoản nội bộ.
- **"Test tự động có phủ hết các case vừa demo không?"** → Không, bộ test tự động đã được gỡ khỏi dự án; các case trên được kiểm chứng thủ công qua thao tác trực tiếp trên UI trong buổi demo.
