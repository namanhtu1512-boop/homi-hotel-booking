# Kịch bản demo 1 người — đủ 4 vai trên 1 laptop

Dành cho tình huống **1 người thao tác hết**, trình bày tuần tự: **Khách vãng
lai → Khách đăng nhập → Lễ tân → Admin**. Nội dung chi tiết từng thao tác vẫn
tra theo **Phần X** ghi kèm trong `docs/demo-script.md` — file này chỉ nêu
**thứ tự làm trên 1 laptop** và **lúc nào cần đổi tab/tài khoản**.

## ⚠️ Lưu ý quan trọng về tab "ẩn danh" trước khi làm

Nhiều cửa sổ **Ẩn danh (Incognito/InPrivate) mở cùng lúc trong CÙNG 1 trình
duyệt dùng CHUNG 1 session** — đăng nhập ở cửa sổ ẩn danh này sẽ tự động
"dính" sang cửa sổ ẩn danh khác đang mở, **không tách biệt như nhiều người
tưởng**. Nếu mở 3 cửa sổ ẩn danh rồi đăng nhập customer/staff/admin ở mỗi
cửa sổ, tài khoản đăng nhập sau cùng sẽ đè lên các tab kia.

Cách làm an toàn cho demo (chọn 1 trong 2):

- **Cách đơn giản, khuyên dùng khi đi thi**: chỉ dùng **1 cửa sổ duy nhất**
  (ẩn danh hoặc thường đều được), đi tuần tự từng vai, **đăng xuất trước khi
  đăng nhập vai tiếp theo**. Không có rủi ro kỹ thuật, không cần chuẩn bị gì
  thêm. Tất cả các bước bên dưới viết theo cách này.
- **Cách "chạy song song" cho ấn tượng hơn** (thấy cập nhật theo thời gian
  thực mà không cần bấm đăng xuất): mở **nhiều hồ sơ Chrome khác nhau**
  (`chrom://settings` → Thêm hồ sơ, hoặc `Ctrl+Shift+M`) hoặc **nhiều trình
  duyệt khác nhau** (VD Chrome cho Khách, Edge cho Lễ tân, Firefox cho
  Admin) — mỗi hồ sơ/trình duyệt có session hoàn toàn riêng, giữ đăng nhập
  song song thật sự. Chỉ nên dùng cách này nếu đã tập dượt trước, tránh lúng
  túng đổi cửa sổ khi đang thi.

Kịch bản dưới đây viết theo **cách đơn giản (1 cửa sổ, tuần tự)** — ở mỗi chỗ
cần "thấy kết quả phía bên kia", sẽ ghi rõ *"đăng xuất → đăng nhập vai khác →
quay lại sau"*. Nếu bạn dùng nhiều hồ sơ song song, chỉ cần đổi "đăng
xuất/đăng nhập" thành "chuyển sang cửa sổ vai đó" là dùng được y nguyên thứ tự
này.

## Chuẩn bị (làm ngay trước buổi demo)

Làm đúng mục **"Chuẩn bị"** trong `docs/demo-script.md`: chạy
`php artisan db:seed --class=DemoFlowSeeder`, ghi lại mã đơn A–K in ra. Lệnh
an toàn chạy nhiều lần.

| Vai | Tài khoản | Cổng đăng nhập |
|---|---|---|
| Khách vãng lai | — | không đăng nhập |
| Khách hàng | `customer@homi.test` / `123456` (phụ: `user@gmail.com`) | `/customer/login` |
| Lễ tân | `staff@homi.test` / `123456` | `/staff/login` |
| Admin | `admin@homi.test` / `123456` | `/admin/login` |

---

## PHẦN 0 — KHÁCH VÃNG LAI, CHƯA ĐĂNG NHẬP (~3–4 phút)

Mở đầu buổi demo, **chưa đăng nhập gì cả** — cho hội đồng thấy độ đầy đủ của
web trước khi vào nghiệp vụ đặt phòng.

1. `/` — trang chủ, banner marketing.
2. `/about` — giới thiệu khách sạn.
3. `/rooms` — danh sách loại phòng. Thử **bộ lọc tìm phòng**: chọn ngày
   nhận/trả phòng + số khách + số lượng phòng cần → hệ thống tự **gợi ý tổ
   hợp phòng phù hợp nhất** thay vì bắt khách tự chọn từng loại — nói rõ giới
   hạn tối đa 4 phòng/8 khách một lần tìm. Có badge giảm giá nếu đang trong
   1 đợt giá theo mùa.
4. `/rooms/{id}` — chi tiết 1 phòng: sức chứa người lớn/trẻ em tách riêng,
   tiện nghi, đánh giá thật của khách đã ở (giải thích nguồn dữ liệu này ở
   Phần 6 dưới).
5. `/promotions` — mã khuyến mãi công khai đang chạy.
6. `/news` — tin tức/ưu đãi.
7. **Trợ lý ảo AI** (nút robot góc dưới phải) — hoạt động **ngay cả khi chưa
   đăng nhập** (khác khung "Hỗ trợ" chat với nhân viên, khung đó bắt buộc
   đăng nhập — xem Phần 3 bên dưới). Hỏi thử *"Khách sạn có loại phòng
   nào?"*, *"Phòng Superior còn trống ngày [x] không?"* → nhờ đặt phòng luôn
   sẽ bị AI từ chối vì không có quyền tạo đơn.
8. `/contact` — gửi 1 tin nhắn liên hệ, không cần đăng nhập (tin này sẽ xem
   lại ở `/admin/contact-messages` trong Phần Admin).
9. `/group-bookings` — form tư vấn đặt đoàn: nhập ngày + số khách để hệ
   thống **gợi ý phương án phòng** (không cần đăng nhập). Lưu ý: đây chỉ là
   xem gợi ý — **gửi yêu cầu tư vấn thật** cần đăng nhập tài khoản khách
   hàng, nên để demo thật ở Phần Lễ tân (yêu cầu J đã seed sẵn, khỏi cần gửi
   tay).
10. Trỏ chuột qua 2 nút "Đăng nhập với Google/Facebook" ở `/customer/login`
    để nhắc tới OAuth, không cần bấm thật nếu môi trường demo offline.

---

## PHẦN 1 — KHÁCH HÀNG (~8–10 phút)

Đăng nhập `customer@homi.test` ngay tại `/customer/login`.

1. **Đặt phòng mới trực tiếp**: `/customer/bookings/create`, chọn loại phòng
   + ngày + số khách (minh họa chặn vượt sức chứa), thêm dịch vụ kèm theo.
2. **Thanh toán** — chọn **đặt cọc 30% (mô phỏng)** để đơn tự chuyển "Đã xác
   nhận" ngay, không phải chờ ai xử lý thêm (đơn giản nhất cho demo 1 người).
   *(Nếu muốn minh họa thêm cách chuyển khoản QR: đặt 1 đơn thứ 2, báo "đã
   chuyển khoản" → đơn này sẽ để đó, xác nhận ở Phần Lễ tân bên dưới rồi quay
   lại xem sau.)*
3. **Hỏi AI trợ lý**: hỏi loại phòng, hỏi phòng trống ngày cụ thể, thử nhờ AI
   đặt phòng luôn (bị từ chối, hướng sang form/chat).
4. **Gửi tin nhắn chat hỗ trợ**: menu "Hỗ trợ" (`/customer/chat`) → gửi 1
   tin. *(Để đó — sẽ trả lời ở Phần Lễ tân, quay lại xem ở Phần cuối.)*
5. **Yêu thích**: bấm tim ở 1 phòng, xem `/customer/wishlist`.
6. **Đơn đã hoàn tất & đánh giá**: `/rooms/{id}` của Phòng Suite — xem đánh
   giá 5 sao đã seed sẵn (kịch bản E), giải thích khách viết đánh giá sau khi
   đơn hoàn tất — sẽ viết đánh giá sống trên đơn khác ở Phần cuối.
7. *(Tùy chọn)* Đăng nhập `user@gmail.com` xem đơn **F** đã hủy + phí hủy
   theo bậc + đã hoàn tiền.

**Đăng xuất** trước khi sang vai Lễ tân.

---

## PHẦN 2 — LỄ TÂN (~10 phút)

Đăng nhập `staff@homi.test` tại `/staff/login`.

1. **Xác nhận chuyển khoản** (chỉ nếu ở Phần 1 có đặt đơn 2 trả bằng chuyển
   khoản QR) — `/staff/payments` → tìm đơn vừa báo → xác nhận.
2. **Nhận phòng đơn B** — `/staff/bookings` → tìm B → Nhận phòng → chọn
   phòng vật lý.
3. **Xử lý yêu cầu nhận phòng sớm đơn H** — mục "Yêu cầu nhận phòng sớm" →
   duyệt (phí 300.000đ) → quay lại nhận phòng H bình thường (không còn bị
   chặn vì đã có yêu cầu được duyệt).
4. **Trong lưu trú đơn C** — 🔵 Dịch vụ, 🔴 Hỏng/mất đồ, 🟠 Vi phạm, 🟡 Vệ
   sinh đặc biệt, gia hạn thêm đêm, rồi **Trả phòng** → đơn C chuyển "Hoàn
   tất" (dùng để viết đánh giá sống ở Phần cuối).
5. **Xử lý yêu cầu trả phòng muộn đơn I** — duyệt (phí 450.000đ) → trả phòng
   I.
6. **Xử lý yêu cầu đổi phòng đơn G** — duyệt 1 yêu cầu; nếu có thời gian,
   thử thêm 1 yêu cầu khác và bấm **Từ chối** kèm lý do.
7. **Xử lý yêu cầu tư vấn đặt đoàn đơn J** — đánh dấu đã liên hệ → gửi báo
   giá qua chat → tạo đơn thật từ yêu cầu.
8. **Đặt phòng tại quầy** — `/staff/bookings/create`, đặt hộ 1 khách vãng
   lai → chỉ ra nhật ký ghi "Tạo đơn tại quầy", phân biệt với đơn khách tự
   đặt online.
9. **Đề xuất giảm giá đoàn thêm** cho 1 đơn đủ điều kiện — nhập % → gửi (sẽ
   duyệt ở Phần Admin).
10. **Trả lời chat hỗ trợ** của khách (tin nhắn gửi ở Phần 1 bước 4) — hộp
    thư chung, ai đăng nhập cũng thấy và trả lời được.
11. **Dashboard** `/staff/dashboard` — cảnh báo "Quá hạn trả phòng" (đơn D).

**Đăng xuất** trước khi sang vai Admin.

---

## PHẦN 3 — ADMIN (~10–12 phút)

Đăng nhập `admin@homi.test` tại `/admin/login`.

1. **Duyệt mã khuyến mãi nhóm** đề xuất sẵn `DEMOVIP10` (kịch bản K) —
   `/admin/promotions` → mục đề xuất chờ → Duyệt.
2. **Duyệt đề xuất giảm giá đoàn** mà Lễ tân vừa gửi (Phần 2 bước 9) — mục
   "Yêu cầu giảm giá đoàn" → Duyệt (có thể chỉnh % khác đề xuất).
3. **Quản lý đánh giá** — `/admin/reviews`, xem đánh giá kịch bản E, thử
   ẩn/hiện 1 đánh giá.
4. **Xem đơn F đã hủy + đã hoàn tiền** (bản admin).
5. **Dashboard & audit log** — `/admin/dashboard`, `/admin/audit-logs`
   (admin-only).
6. **Kiểm tra tin nhắn liên hệ** — `/admin/contact-messages`, xem tin gửi ở
   Phần 0.
7. **Giường phụ hết kho** — `/admin/hotel-info` → hạ "Tổng số giường phụ" về
   0 → lưu. *(Để nguyên vậy — thử đặt đơn cần giường phụ ở Phần cuối, quay
   lại đây sau để xử lý yêu cầu và đặt lại về 5.)*
8. **Khóa tài khoản** — `/admin/users` → khóa `user@gmail.com`. *(Để nguyên
   vậy — thử đăng nhập bị chặn ở Phần cuối, quay lại đây sau để mở khóa.)*
9. **Đi nhanh qua cấu hình còn lại**: `/admin/room-types` + `/admin/rooms`,
   `/admin/seasonal-rates`, `/admin/group-discount-policies`,
   `/admin/banners`, `/admin/news`, `/admin/services` +
   `/admin/surcharge-items`.

**Đăng xuất** trước khi quay lại vai Khách hàng.

---

## PHẦN 4 — QUAY LẠI KHÁCH HÀNG: CHỐT VÒNG ĐỜI (~5 phút)

Đăng nhập lại `customer@homi.test`.

1. **Xem chat đã được trả lời** (Phần 2 bước 10) + chuông thông báo có tin
   mới, badge số tin chưa đọc trên menu "Hỗ trợ" cập nhật ngay.
2. **Viết đánh giá** cho đơn **C** vừa trả phòng (Phần 2 bước 4) — có thể
   đính kèm tối đa 2 video bằng chứng, mỗi video ≤ 50MB.
3. **Thử đặt 1 đơn cần giường phụ** (số khách vượt sức chứa, tick "cần
   giường phụ") — vì Admin vừa hạ kho về 0 (Phần 3 bước 7), đơn sẽ tự chuyển
   **"Chờ tư vấn"** thay vì xác nhận thẳng, đồng thời sinh 1 "Yêu cầu giường
   phụ".
4. **Thử đăng nhập lại `user@gmail.com`** — bị chặn với thông báo "Tài khoản
   đang bị khóa hoặc chưa hoạt động" (Admin vừa khóa ở Phần 3 bước 8).

**Đăng xuất → đăng nhập lại Admin** để dọn lại trạng thái + chốt 2 việc còn
treo:

5. Xử lý "Yêu cầu giường phụ" vừa sinh ra → **đặt lại số giường phụ về 5**.
6. **Mở khóa** `user@gmail.com`.

**Đăng xuất → đăng nhập lại Khách hàng** một lần cuối: đăng nhập
`user@gmail.com` thành công — chốt trọn vòng khóa → chặn → mở khóa → đăng
nhập lại được.

---

## Phụ lục

Danh sách mã kịch bản A–K, cách seed lại, và giải thích chi tiết từng thao
tác (tại sao có bước này, luật nghiệp vụ gì đứng sau): xem
`docs/demo-script.md`.
