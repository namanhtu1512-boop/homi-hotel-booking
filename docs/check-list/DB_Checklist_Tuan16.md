# DB Checklist — Tuần 16 (BE2)

## Backup / export schema

Chưa tạo file dump trong repo ở đợt này — để tự chạy khi cần nộp/deploy
(dump chứa cấu trúc thật của máy đang chạy, nên tạo ngay trước lúc đóng gói
thay vì đóng băng sẵn trong repo):

```bash
# Cách 1 (khuyến nghị) — lệnh có sẵn của Laravel, không cần gõ tay
# credential MySQL ra command line:
php artisan schema:dump

# Cách 2 — mysqldump trực tiếp (có sẵn qua Laragon):
mysqldump -u root hotel-booking > docs/backup/homi-schema-$(date +%Y%m%d).sql
```

Cả 2 cách đều tạo file SQL chứa cấu trúc bảng (schema:dump mặc định không
kèm dữ liệu) — phục hồi lại bằng cách import file `.sql` vào MySQL rồi chạy
`php artisan db:seed` để có dữ liệu demo.

## Xác nhận seed chạy sạch (đã verify nhiều lần ở Tuần 14, chạy lại lần cuối)

```bash
php artisan migrate:fresh --seed
```

Kết quả mong đợi (đã đối chiếu bằng `php artisan tinker` ở Tuần 14):

| Bảng | Số dòng | Ghi chú |
|---|---|---|
| `users` | 6 | 3 role × 2 (gồm tài khoản khóa + tài khoản phụ) |
| `amenities` | 10 | Gắn vào `hotel_info` qua pivot |
| `room_types` | 5 | Standard/Superior/Deluxe/Family/Suite, đủ ảnh thật (Unsplash) |
| `hotel_info_images` | 4 | Trước đây là 0 — đã vá ở Tuần 14 |
| `bookings` | 8 | 3 case demo (pending/confirmed/cancelled) + 3 overlap (Suite) + 2 completed có đánh giá |
| `reviews` | 2 | Trước đây là 0 — đã vá ở Tuần 14 |
| `promotions` | 4 | 3 active + 1 hết hạn (dùng để demo "mã không hợp lệ") |
| `banners` | 3 | |
| `news` | 5 | |
| `contact_messages` | 2 | Trước đây là 0 — đã vá ở Tuần 14 |

## Restore trên máy khác (thầy chấm bài / máy mới)

```bash
git clone <repo-url>
cd homi-hotel-booking
composer run setup   # tự làm: install → .env → key:generate → migrate --seed → storage:link → npm build
```

Không cần import file `.sql` thủ công trong trường hợp bình thường — migration
tự tạo cấu trúc bảng, không cần tới `schema.sql`. File dump ở trên chỉ cần
khi muốn khôi phục nhanh trên môi trường không chạy được `php artisan migrate`
(ví dụ import thẳng vào 1 MySQL server quản lý qua hosting panel).
