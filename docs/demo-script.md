# Kịch bản demo bảo vệ đồ án — Homi Hotel Booking

Kịch bản này đi qua **toàn bộ vòng đời 1 đơn đặt phòng**: đặt phòng → thanh
toán → nhận phòng → phát sinh giữa kỳ ở → trả phòng → hoàn tất → đánh giá,
cùng các nhánh phụ (hủy/hoàn tiền, đổi phòng, nhận phòng sớm/trả phòng muộn có
duyệt, đặt đoàn, khuyến mãi nhóm) và góc nhìn vận hành (dashboard, lịch sử
phòng). Đi kèm là `database/seeders/DemoFlowSeeder.php` — dựng sẵn dữ liệu cho
từng bước để không phải thao tác tay từ đầu ngay trên sân khấu.

## Chuẩn bị (làm ngay trước buổi demo)

1. Đảm bảo MySQL đang chạy (Laragon), server đang chạy (`php artisan serve`
   hoặc qua Laragon).
2. Chạy seeder dữ liệu demo:
   ```
   php artisan db:seed --class=DemoFlowSeeder
   ```
   Lệnh này **an toàn chạy nhiều lần** — kịch bản nào đã có sẽ tự bỏ qua, chỉ
   tạo thêm phần còn thiếu. Cuối lệnh sẽ in ra bảng mã đơn của từng kịch bản —
   **chụp lại màn hình này hoặc ghi ra giấy**, sẽ cần để gõ mã đơn khi demo.
3. **Quan trọng**: kịch bản A (đơn chờ thanh toán) chỉ giữ chỗ 15 phút kể từ
   lúc seed — nếu seed từ lâu trước giờ demo, đơn này có thể đã tự hủy. Nếu
   vậy, phần "đặt phòng mới" nên làm sống trực tiếp (xem Phần 2) thay vì dùng
   đơn có sẵn — thật ra cách này còn ấn tượng hơn khi trình bày trực tiếp.
4. Tra cứu lại mã đơn demo bất kỳ lúc nào (nếu quên/không chụp kịp), chạy:
   ```
   php artisan tinker --execute="foreach (['A','B','C','D','E','F','G','H','I'] as $t) { $b = \App\Models\Booking::where('note','like','[DEMO-'.$t.']%')->first(); echo $t.': '.($b->booking_code ?? 'chưa có').' ('.($b->status->value ?? '').')'.PHP_EOL; }"
   ```

### Tài khoản đăng nhập

| Vai trò | Trang đăng nhập | Email | Mật khẩu |
|---|---|---|---|
| Khách hàng (chính) | `/customer/login` | `customer@homi.test` | `123456` |
| Khách hàng (phụ, dùng ở vài kịch bản) | `/customer/login` | `user@gmail.com` | `123456` |
| Lễ tân / nhân viên | `/staff/login` | `staff@homi.test` | `123456` |
| Quản trị viên | `/admin/login` | `admin@homi.test` | `123456` |

---

## Phần 1 — Trang công khai (1–2 phút, mở đầu)

- `/` — trang chủ, giới thiệu khách sạn.
- `/rooms` — danh sách loại phòng, giá, có thể thấy badge giảm giá nếu có
  seasonal rate đang active.
- Mở 1 phòng bất kỳ ở `/rooms/{id}` — chỉ ra: sức chứa (người lớn/trẻ em
  riêng), đánh giá của khách (mục Phần 6 sẽ giải thích dữ liệu này từ đâu ra).
- `/promotions` — mã khuyến mãi hiện có.

---

## Phần 2 — Đặt phòng mới & thanh toán (làm SỐNG trực tiếp)

1. Đăng nhập khách hàng (`customer@homi.test`).
2. Vào `/customer/bookings/create`, chọn 1 loại phòng, ngày ở bất kỳ, khai số
   người lớn/trẻ em/trẻ sơ sinh — chỉ ra: hệ thống chặn cứng nếu vượt sức
   chứa, trẻ em ngoài sức chứa cần tick "cần giường phụ", trẻ sơ sinh miễn phí
   không tính vào sức chứa.
3. Có thể chọn thêm dịch vụ (ăn sáng, đưa đón...) ngay lúc đặt.
4. Đặt xong → đơn ở trạng thái **"Chờ đặt cọc/thanh toán"**, đồng hồ đếm
   ngược 15 phút.
5. Thanh toán — chọn 1 trong 3 cách để minh họa:
   - **Đặt cọc 30%** (mô phỏng) — phần còn lại trả tiền mặt khi nhận phòng.
   - **Chuyển khoản QR** — khách tự báo đã chuyển, chuyển sang "chờ đối
     soát"; sang tài khoản `staff@homi.test` → `/staff/payments` để xác nhận
     thủ công.
   - **VNPay sandbox** — redirect thật sang cổng VNPay test. *(Cần
     `VNPAY_TMN_CODE`/`VNPAY_HASH_SECRET` thật trong `.env`; nếu môi trường
     demo không có mạng ra ngoài, dùng 1 trong 2 cách trên thay thế.)*
6. Sau khi thanh toán, đơn tự chuyển **"Đã xác nhận"** — không cần bước duyệt
   thủ công nào thêm.

*(Nếu không muốn gõ form: đơn seed sẵn kịch bản **A** đang ở đúng bước 4, chỉ
cần đăng nhập và vào thẳng trang chi tiết đơn để bấm thanh toán — nhưng nhớ
seed lại ngay trước đó, xem lưu ý ở phần Chuẩn bị.)*

---

## Phần 3 — Nhận phòng (Check-in)

Dùng kịch bản **B** (đã xác nhận + đã thanh toán, ngày nhận phòng = hôm nay).

1. Đăng nhập `staff@homi.test` (hoặc `admin@homi.test`).
2. `/staff/bookings` → tìm đơn B (theo mã đơn đã ghi) → "Nhận phòng".
3. Chọn phòng vật lý cụ thể cho từng loại phòng trong đơn → xác nhận.
4. Nếu đến **trước** giờ nhận phòng chuẩn của khách sạn (mặc định 14:00) mà
   **chưa có** yêu cầu nhận phòng sớm được duyệt, hệ thống sẽ chặn và yêu cầu
   duyệt trước — đây là tính năng thật (chống nhận phòng sớm "chui"), có thể
   minh họa bằng cách nhảy sang Phần 5a trước rồi quay lại.

---

## Phần 4 — Trong thời gian lưu trú

Dùng kịch bản **C** (đang lưu trú, nhận phòng từ hôm qua, còn 1 đêm nữa).

Trên trang chi tiết đơn (`/staff/bookings/{id}` hoặc `/admin/bookings/{id}`):

1. **Thêm dịch vụ** — chọn 1 dịch vụ trong catalog (vd "Ăn sáng buffet"),
   ghi vào "hóa đơn phát sinh" riêng (không đụng tiền phòng gốc).
2. **Thêm phụ phí** — vd phí hư hỏng đồ đạc, chọn từ danh mục phụ phí có sẵn
   hoặc nhập tay.
3. **Gia hạn thêm đêm** — chọn ngày trả phòng mới; nếu loại phòng hiện tại
   hết chỗ, hệ thống gợi ý phương án đổi sang loại phòng khác còn trống.
4. **Trả phòng** — bấm "Trả phòng", xem toàn bộ hóa đơn phát sinh được gộp
   vào 1 lần thu, đơn chuyển thẳng sang **"Hoàn tất"**.
5. *(Tùy chọn)* đăng nhập lại `customer@homi.test`, vào đơn vừa hoàn tất →
   viết đánh giá ngay — nối tiếp sang Phần 6.

---

## Phần 5 — Các luồng yêu cầu cần duyệt

### 5a. Nhận phòng sớm (kịch bản H)

Đơn H đã xác nhận + thanh toán, có sẵn 1 **yêu cầu nhận phòng sớm đang chờ
duyệt** (khách xin đến sớm 2h30, trước giờ chuẩn 14:00).

1. `staff`/`admin` → "Yêu cầu nhận phòng sớm" → mở yêu cầu của đơn H → Duyệt.
2. Phí cố định (300.000đ cho 3 giờ, làm tròn lên) được ghi vào hóa đơn phát
   sinh của đơn.
3. Nhận phòng ngay đơn H (giống Phần 3) — không còn bị chặn vì đã có yêu cầu
   được duyệt.

### 5b. Trả phòng muộn (kịch bản I)

Đơn I đang lưu trú, có sẵn 1 **yêu cầu trả phòng muộn đang chờ duyệt** (khách
xin trả tới 15:00, giờ chuẩn 12:00).

1. "Yêu cầu trả phòng muộn" → mở yêu cầu của đơn I → Duyệt (phí 750.000đ, 50% giá phòng Deluxe).
2. Trả phòng đơn I — phí vừa duyệt đã nằm sẵn trong hóa đơn phát sinh, thu 1
   lần cùng lúc trả phòng.

### 5c. Đổi loại phòng (kịch bản G)

Đơn G đã xác nhận, **chưa nhận phòng**, có sẵn 1 yêu cầu đổi từ Phòng Standard
sang Phòng Deluxe.

1. "Yêu cầu đổi phòng" → mở yêu cầu của đơn G.
2. Duyệt → hệ thống tự tính lại giá + kiểm tra phòng trống cho loại phòng
   mới, cập nhật thẳng vào đơn; nếu đơn đã thanh toán đủ mà tiền chênh lệch
   khác 0, thanh toán tự mở lại "chờ thu thêm/hoàn lại".
3. *(Tùy chọn)* thử lại với 1 yêu cầu khác và bấm **Từ chối** kèm lý do, để
   khách xem được lý do trên trang của họ.

---

## Phần 6 — Đơn đã hoàn tất & đánh giá (kịch bản E)

- `/rooms/{id}` của Phòng Suite — đánh giá 5 sao đã seed sẵn hiển thị ngay
  trong phần đánh giá của phòng.
- `admin.reviews.index` (`/admin/reviews`) — quản lý đánh giá: ẩn/hiện, xóa.

---

## Phần 7 — Hủy đơn & hoàn tiền (kịch bản F)

Đơn F đã thanh toán chuyển khoản rồi bị khách hủy **ngay trong ngày nhận
phòng** — minh họa chính sách phí hủy theo bậc giờ còn lại (càng sát giờ nhận
phòng, phí hủy càng cao, tối đa mất 100%).

- Vào `/customer/bookings/{id}` (đăng nhập `user@gmail.com`) hoặc trang admin
  tương ứng — xem trạng thái "Đã hủy", phí hủy đã áp, và thanh toán đã
  chuyển sang "Đã hoàn tiền" (xử lý thủ công vì phương thức là chuyển khoản,
  không qua cổng VNPay).

---

## Phần 8 — Góc nhìn vận hành khách sạn

- `/admin/dashboard` hoặc `/staff/dashboard` — thẻ cảnh báo
  **"⚠️ Quá hạn trả phòng"** hiện sẵn nhờ kịch bản **D** (đang lưu trú nhưng
  đã quá ngày trả phòng đã đặt 1 ngày).
- Trang lịch sử phòng (`admin.rooms.show` / `staff.rooms.show`, bấm vào 1
  phòng bất kỳ từ `/admin/rooms`) — nhật ký ai/khi nào/làm gì trên phòng đó
  (nhận phòng, trả phòng, cập nhật thanh toán, dọn phòng...).
- `/admin/audit-logs` — nhật ký thao tác toàn hệ thống (chỉ admin có).

---

## Phần 9 — Đặt đoàn & khuyến mãi nhóm

### 9a. Yêu cầu tư vấn đặt đoàn (kịch bản J)

Có sẵn 1 yêu cầu tư vấn đoàn 12 người, 6 phòng, gửi qua form công khai
`/group-bookings`.

- `staff`/`admin` → "Yêu cầu đặt đoàn" → mở yêu cầu → có thể **đánh dấu đã
  liên hệ**, **gửi báo giá** (tự tính tổng tiền ước tính kèm ưu đãi), hoặc
  **tạo đơn thật** trực tiếp từ yêu cầu này.

### 9b. Đề xuất mã khuyến mãi nhóm (kịch bản K)

Nhân viên đã đề xuất sẵn mã `DEMOVIP10` (giảm 10%), đang chờ admin duyệt.

- Đăng nhập `admin@homi.test` → trang Khuyến mãi (`/admin/promotions`) → mục
  đề xuất đang chờ → Duyệt (tự tạo mã khuyến mãi thật, kích hoạt ngay) hoặc
  Từ chối.

---

## Phụ lục — Danh sách kịch bản & trạng thái seed sẵn

| Mã | Trạng thái khi seed xong | Dùng để demo |
|---|---|---|
| A | Chờ đặt cọc/thanh toán (hết hạn sau 15 phút) | Đặt phòng & thanh toán |
| B | Đã xác nhận, đã thanh toán, nhận phòng hôm nay | Check-in |
| C | Đang lưu trú (từ hôm qua, còn 1 đêm) | Dịch vụ/phụ phí/gia hạn/trả phòng |
| D | Đang lưu trú, quá hạn trả phòng 1 ngày | Cảnh báo dashboard |
| E | Đã hoàn tất, đã có đánh giá 5 sao | Hiển thị đánh giá |
| F | Đã hủy (hủy ngay trong ngày nhận phòng), đã hoàn tiền | Phí hủy theo bậc + hoàn tiền |
| G | Đã xác nhận, có yêu cầu đổi phòng chờ duyệt | Duyệt/từ chối đổi phòng |
| H | Đã xác nhận, có yêu cầu nhận phòng sớm chờ duyệt | Duyệt nhận phòng sớm |
| I | Đang lưu trú, có yêu cầu trả phòng muộn chờ duyệt | Duyệt trả phòng muộn |
| J | Yêu cầu tư vấn đặt đoàn (public form) | Xử lý yêu cầu đoàn |
| K | Đề xuất mã khuyến mãi nhóm chờ duyệt | Duyệt khuyến mãi nhóm |

Nguồn seed: `database/seeders/DemoFlowSeeder.php` (chạy độc lập, không nằm
trong `DatabaseSeeder` mặc định).
