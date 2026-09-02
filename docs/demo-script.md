# Kịch bản demo bảo vệ đồ án — Homi Hotel Booking

Kịch bản này đi qua **toàn bộ vòng đời 1 đơn đặt phòng**: đặt phòng → thanh
toán → nhận phòng → phát sinh giữa kỳ ở → trả phòng → hoàn tất → đánh giá,
cùng các nhánh phụ (hủy/hoàn tiền, đổi phòng, trả phòng muộn có duyệt, đặt
đoàn, khuyến mãi nhóm) và góc nhìn vận hành (dashboard, lịch sử
phòng). Đi kèm là `database/seeders/DemoFlowSeeder.php` — dựng sẵn dữ liệu cho
từng bước để không phải thao tác tay từ đầu ngay trên sân khấu.

Phần 1–9 là mạch chính (vòng đời 1 đơn). **Phần 10–15** đi tiếp qua toàn bộ
tính năng còn lại của hệ thống — không nằm trong vòng đời 1 đơn nên tách
riêng: đăng nhập mạng xã hội & tài khoản bị khóa, trợ lý ảo AI & chat hỗ trợ,
yêu thích & thông báo, đặt phòng tại quầy & giường phụ hết tồn kho, giảm giá
đoàn theo đơn, và các trang quản trị cấu hình/nội dung.

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
| Khách hàng bị khóa (demo Phần 10/15) | `/customer/login` | `locked@homi.test` | `123456` |
| Lễ tân / nhân viên | `/staff/login` | `staff@homi.test` | `123456` |
| Quản trị viên | `/admin/login` | `admin@homi.test` | `123456` |

Đăng nhập bằng Google/Facebook chỉ có ở `/customer/login` (không có ở cổng lễ
tân/admin) — xem Phần 10.

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
3. Chọn phòng vật lý cụ thể cho từng loại phòng trong đơn → xác nhận. Nhận
   phòng sớm hơn giờ chuẩn của khách sạn (mặc định 14:00) hoàn toàn tự do,
   không cần duyệt, không thu phí.

---

## Phần 4 — Trong thời gian lưu trú

Dùng kịch bản **C** (đang lưu trú, nhận phòng từ hôm qua, còn 1 đêm nữa).

Trên trang chi tiết đơn (`/staff/bookings/{id}` hoặc `/admin/bookings/{id}`):

1. **🔵 Dịch vụ** — chọn 1 dịch vụ trong catalog (vd "Ăn sáng buffet"), ghi
   vào "hóa đơn phát sinh" riêng (không đụng tiền phòng gốc).
2. **3 nhóm phụ phí còn lại** trong "Thao tác trong lưu trú" — **🔴 Hỏng/mất
   đồ**, **🟠 Vi phạm quy định**, **🟡 Vệ sinh đặc biệt** — mỗi nhóm có danh
   mục phụ phí riêng, chọn từ danh mục có sẵn hoặc nhập tay số tiền.
3. **Gia hạn thêm đêm** — chọn ngày trả phòng mới; nếu loại phòng hiện tại
   hết chỗ, hệ thống gợi ý phương án đổi sang loại phòng khác còn trống.
4. **Trả phòng** — bấm "Trả phòng", xem toàn bộ hóa đơn phát sinh được gộp
   vào 1 lần thu, đơn chuyển thẳng sang **"Hoàn tất"**.
5. *(Tùy chọn)* đăng nhập lại `customer@homi.test`, vào đơn vừa hoàn tất →
   viết đánh giá ngay (có thể đính kèm tối đa 2 video bằng chứng, mỗi video
   ≤ 50MB) — nối tiếp sang Phần 6.

---

## Phần 5 — Các luồng yêu cầu cần duyệt

### 5a. Trả phòng muộn (kịch bản I)

Đơn I đang lưu trú, có sẵn 1 **yêu cầu trả phòng muộn đang chờ duyệt** (khách
xin trả tới 15:00, giờ chuẩn 12:00).

1. "Yêu cầu trả phòng muộn" → mở yêu cầu của đơn I → Duyệt (phí 450.000đ, 30% giá phòng Deluxe — trễ 3 giờ).
2. Trả phòng đơn I — phí vừa duyệt đã nằm sẵn trong hóa đơn phát sinh, thu 1
   lần cùng lúc trả phòng.

### 5b. Đổi loại phòng (kịch bản G)

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

## Phần 10 — Đăng nhập: mạng xã hội & tài khoản bị khóa (2 phút)

1. Trang `/customer/login` (hoặc `/register`) có thêm 2 nút **"Đăng nhập với
   Google"** / **"Đăng nhập với Facebook"** — bấm vào sẽ redirect thật sang
   `/auth/{google|facebook}` rồi `/auth/{provider}/callback` để đăng nhập
   hoặc tự tạo tài khoản mới. *(Cần `GOOGLE_CLIENT_ID`/`FACEBOOK_CLIENT_ID`
   thật trong `.env` + mạng ra ngoài; nếu môi trường demo offline, chỉ cần
   trỏ chuột vào nút và giải thích luồng OAuth, không cần bấm thật.)*
2. Thử đăng nhập bằng tài khoản đã bị khóa: `locked@homi.test` / `123456`
   tại `/customer/login` → hệ thống chặn ngay với thông báo **"Tài khoản
   đang bị khóa hoặc chưa hoạt động."** — minh họa cho nút khóa/mở khóa tài
   khoản của admin (xem Phần 15).
3. Lưu ý: `/staff/login` và `/admin/login` là 2 cổng đăng nhập tách riêng
   khỏi khách hàng, không có nút đăng nhập mạng xã hội.

---

## Phần 11 — Trợ lý ảo AI & Chat hỗ trợ trực tuyến (3 phút)

1. Trên mọi trang công khai/khách hàng có 1 nút tròn hình robot ở góc dưới
   bên phải (không xuất hiện ở layout lễ tân/admin) → bấm để mở khung **trợ
   lý ảo AI**. Hỏi thử: *"Khách sạn có những loại phòng nào?"* rồi *"Phòng
   Superior còn trống ngày [chọn 1 ngày] không?"* — chỉ ra: AI trả lời dựa
   trên dữ liệu thật (gọi thẳng `AvailabilityService` qua 2 công cụ
   `list_room_types`/`check_room_availability`), không tự bịa số phòng
   trống. Hỏi tiếp *"Đặt giúp tôi phòng đó luôn"* — AI sẽ từ chối và hướng
   khách sang khung "Hỗ trợ" hoặc form đặt phòng, vì AI không có quyền tạo/
   sửa/hủy đơn.
2. Chat hỗ trợ với người thật: đăng nhập `customer@homi.test` → menu **"Hỗ
   trợ"** (`/customer/chat`) → gửi 1 tin nhắn bất kỳ.
3. Mở tab khác, đăng nhập `staff@homi.test` (hoặc `admin@homi.test`) → cũng
   vào mục "Hỗ trợ"/Chat → chỉ ra đây là **hộp thư chung** (không phân theo
   nhân viên phụ trách, ai đăng nhập cũng thấy và trả lời được mọi khách) →
   trả lời tin nhắn.
4. Quay lại tài khoản khách hàng → chuông thông báo có tin mới và badge số
   tin chưa đọc trên menu "Hỗ trợ" cập nhật ngay.

---

## Phần 12 — Yêu thích & Thông báo (1 phút)

1. `/rooms` hoặc trang chi tiết 1 phòng — bấm biểu tượng trái tim để thêm
   vào **Yêu thích** → menu "Yêu thích (n)" trên thanh điều hướng cập nhật
   số lượng ngay.
2. `/customer/wishlist` — xem danh sách phòng đã lưu, có thể bỏ yêu thích.
3. Chuông thông báo trên thanh điều hướng khách hàng — gộp các sự kiện liên
   quan tới tài khoản (tin nhắn chat mới, yêu cầu được duyệt...), bấm để
   đánh dấu đã đọc.

---

## Phần 13 — Đặt phòng tại quầy & giường phụ hết tồn kho chung (3 phút)

1. Đăng nhập `staff@homi.test` → `/staff/bookings/create` — **đặt phòng tại
   quầy**: cùng 1 form nghiệp vụ với khách tự đặt (Phần 2), nhưng do lễ tân
   điền thay cho khách đến trực tiếp quầy, không cần khách có tài khoản.
   Đặt xong, nhật ký thao tác của đơn (Phần 8) ghi rõ hành động **"Tạo đơn
   tại quầy"** — phân biệt với đơn khách tự đặt online.
2. Giường phụ dùng chung **1 kho duy nhất cho toàn khách sạn** (mặc định 5
   giường, phụ phí 200.000đ/đêm/giường — cấu hình ở Phần 15). Để minh họa
   lúc hết giường phụ: vào `/admin/hotel-info`, tạm hạ **"Tổng số giường
   phụ"** xuống 0, lưu lại → quay lại đặt 1 đơn cần giường phụ (số khách
   vượt sức chứa phòng, tick "cần giường phụ") → đơn tự chuyển trạng thái
   **"Chờ tư vấn"** thay vì xác nhận thẳng, đồng thời sinh 1 **"Yêu cầu
   giường phụ"** chờ xử lý trong mục cùng tên (staff/admin) → nhớ đặt lại số
   giường phụ về 5 sau khi demo xong.

---

## Phần 14 — Giảm giá đoàn theo đơn (khác mã khuyến mãi ở Phần 9b) (2 phút)

Đây là giảm giá áp **trực tiếp vào 1 đơn cụ thể** đủ điều kiện "đoàn" (đủ số
phòng/khách theo `GroupDiscountPolicy` cấu hình ở Phần 15) — khác với mã
khuyến mãi dùng chung cho nhiều khách ở Phần 9b.

1. Trên trang chi tiết 1 đơn đủ điều kiện đoàn (staff) — mục **"Đề xuất
   giảm giá đoàn thêm"**: nhập % giảm (bị giới hạn trần theo chính sách đang
   áp dụng) → gửi → trạng thái "Chờ duyệt".
2. Đăng nhập `admin@homi.test` → **"Yêu cầu giảm giá đoàn"** → mở đề xuất
   vừa gửi → **Duyệt** (có thể điều chỉnh % khác với đề xuất của lễ tân)
   hoặc **Từ chối**.
3. *(Tùy chọn)* Admin cũng có thể vào thẳng 1 đơn đủ điều kiện để áp % giảm
   giá đoàn trực tiếp, không bị giới hạn trần chính sách như lễ tân.

---

## Phần 15 — Quản trị: cấu hình & nội dung hệ thống (4–5 phút)

Đăng nhập `admin@homi.test`, đi nhanh qua các trang cấu hình (lễ tân dùng
song song một phần, trừ các mục có ghi chú admin-only):

- **Thông tin khách sạn** (`/admin/hotel-info`) — mô tả, giờ nhận/trả phòng
  chuẩn, tổng số giường phụ & phụ phí/đêm (Phần 13), bật/tắt **chế độ bảo
  trì** (ẩn toàn bộ trang công khai, chỉ admin/staff truy cập được).
- **Loại phòng & phòng** (`/admin/room-types`, `/admin/rooms`) — CRUD loại
  phòng (sức chứa người lớn/trẻ em riêng, giá, ảnh, tiện nghi), CRUD phòng
  vật lý, lịch phòng theo ngày, trạng thái dọn phòng. *(Lễ tân dùng chung
  giao diện nhưng không được xóa loại phòng, không được thêm/sửa phòng vật
  lý — admin-only.)*
- **Giá theo mùa** (`/admin/seasonal-rates`) — set giá tăng/giảm theo
  khoảng ngày, hiện badge giảm giá trên `/rooms` (đã thấy ở Phần 1).
- **Khuyến mãi & chính sách giảm giá đoàn** (`/admin/promotions`,
  `/admin/group-discount-policies`) — CRUD mã khuyến mãi công khai, cấu
  hình trần % giảm giá đoàn theo quy mô (dùng ở Phần 14).
- **Nội dung marketing** — Banner trang chủ (`/admin/banners`), Tin tức
  (`/admin/news`), Liên hệ (`/admin/contact-messages` — xem/đánh dấu đã đọc
  tin nhắn gửi từ form `/contact` công khai).
- **Người dùng & khách hàng** (`/admin/users`, `/admin/customers`) — khóa
  tài khoản `user@gmail.com`, thử đăng nhập lại như Phần 10 để thấy bị
  chặn, rồi mở khóa lại.
- **Danh mục dịch vụ & phụ phí** (`/admin/services`, `/admin/surcharge-items`)
  — nguồn dữ liệu cho 4 nhóm "Thao tác trong lưu trú" ở Phần 4 (🔵 Dịch vụ /
  🔴 Hỏng-mất / 🟠 Vi phạm / 🟡 Vệ sinh đặc biệt).

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
| I | Đang lưu trú, có yêu cầu trả phòng muộn chờ duyệt | Duyệt trả phòng muộn |
| J | Yêu cầu tư vấn đặt đoàn (public form) | Xử lý yêu cầu đoàn |
| K | Đề xuất mã khuyến mãi nhóm chờ duyệt | Duyệt khuyến mãi nhóm |

Nguồn seed: `database/seeders/DemoFlowSeeder.php` (chạy độc lập, không nằm
trong `DatabaseSeeder` mặc định).

Phần 10–15 **không** dùng dữ liệu từ `DemoFlowSeeder` — chỉ cần dữ liệu nền
(loại phòng, phòng, khuyến mãi...) đã có sẵn từ `DatabaseSeeder` mặc định,
thao tác trực tiếp ngay lúc demo.
