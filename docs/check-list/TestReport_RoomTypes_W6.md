# TEST REPORT - Module Room Types (Tuần 6)
**Dự án:** Homi Hotel Booking
**Module:** Room Types — dữ liệu, giá, số lượng, ảnh, trạng thái
**BE4 phụ trách:** [Tên thật]
**Tuần:** 6 (15/06 - 21/06/2026)
**Môi trường:** Local - Laravel 13, SQLite in-memory (test env), PHP 8.3

---

## 1. Phạm vi kiểm thử

Theo kế hoạch tuần 6, BE2 phát triển CRUD `room_types`, BE4 kiểm thử toàn bộ phần **dữ liệu**:

- Tạo/sửa loại phòng — dữ liệu lưu đúng, slug tự sinh
- Validation: tên, giá, sức chứa, số lượng phòng
- Cập nhật giá riêng (`PATCH .../price`)
- Cập nhật số lượng phòng riêng (`PATCH .../inventory`)
- Soft delete **vs** chuyển trạng thái `hidden` khi loại phòng đang có booking active — đây là rule nghiệp vụ quan trọng nhất tuần này
- Khôi phục loại phòng đã xóa
- Quản lý ảnh (đường dẫn ảnh, không phải upload file thật — xác nhận từ `ImageService`)
- Dữ liệu lớn: 20 loại phòng / 1 khách sạn

**Không trong phạm vi:** Phân quyền (đã làm ở tuần 5 — xem `AdminRoomTypeAccessTest.php` của BE1), availability/booking thực tế (tuần 9-10).

**Lưu ý môi trường:** Bộ test này được viết bằng cách đọc trực tiếp source code thật (`RoomTypeController`, `RoomTypeService`, `RoomTypePolicy`, `CreateRoomTypeRequest`, migrations, factories) nên khớp với cấu trúc API thực tế. Do giới hạn mạng của môi trường soạn thảo, **chưa tự chạy được `php artisan test`** — bạn cần chạy trên máy local và điền lại bảng kết quả bên dưới.

---

## 2. Cách chạy

```bash
# Đặt file vào đúng vị trí
# tests/Feature/RoomType/RoomTypeDataTest.php

php artisan test tests/Feature/RoomType/RoomTypeDataTest.php -v
```

Nếu muốn chạy riêng từng nhóm:

```bash
php artisan test --filter=test_TC_RTD_06   # nhóm soft-delete vs hidden
php artisan test --filter=test_TC_RTD_03   # nhóm cập nhật giá
php artisan test --filter=test_TC_RTD_04   # nhóm cập nhật số lượng
```

---

## 3. Tổng hợp kết quả (điền sau khi chạy)

| Hạng mục | Tổng TC | Pass | Fail | Tỷ lệ Pass |
|----------|---------|------|------|------------|
| CRUD dữ liệu cơ bản | 3 | | | |
| Validation | 11 | | | |
| Cập nhật giá | 4 | | | |
| Cập nhật số lượng | 5 | | | |
| Cập nhật một phần (PUT) | 3 | | | |
| Soft delete vs Hidden | 5 | | | |
| Khôi phục | 2 | | | |
| Danh sách theo hotel | 4 | | | |
| Ảnh (image paths) | 5 | | | |
| Dữ liệu lớn (20 phòng) | 2 | | | |
| **Tổng** | **44** | | | |

---

## 4. Chi tiết test case quan trọng nhất tuần 6

### Soft delete vs Hidden — lõi nghiệp vụ

| TC ID | Kịch bản | Input | Kỳ vọng | Kết quả | Status |
|-------|----------|-------|---------|---------|--------|
| TC-RTD-060 | Xóa phòng không có booking | room_type không booking | Soft deleted (`deleted_at` có giá trị) | | ⬜ |
| TC-RTD-061 | Xóa phòng có booking `pending` | 1 booking_item status=pending | Không xóa, `status`→`hidden` | | ⬜ |
| TC-RTD-062 | Xóa phòng có booking `confirmed` | 1 booking_item status=confirmed | Không xóa, `status`→`hidden` | | ⬜ |
| TC-RTD-063 | Xóa phòng chỉ có booking `cancelled` | 1 booking_item status=cancelled | Soft deleted bình thường | | ⬜ |
| TC-RTD-064 | Phòng đã xóa không hiện trong list | room_type đã soft delete | Không có trong `data` | | ⬜ |

**Vì sao quan trọng:** Đây là rule trong `RoomTypeService::softDeleteOrDeactivate()` — nếu sai, khách đang có đơn pending sẽ bị mất thông tin phòng đã đặt, gây lỗi nghiêm trọng khi tính toán booking ở tuần 10.

### Validation giá & số lượng

| TC ID | Input | Kỳ vọng | Kết quả | Status |
|-------|-------|---------|---------|--------|
| TC-RTD-011 | price_per_night = -100000 | 422 | | ⬜ |
| TC-RTD-013 | capacity = 0 | 422 | | ⬜ |
| TC-RTD-015 | total_rooms = 0 | 422 | | ⬜ |
| TC-RTD-032 | PATCH price = -1 | 422 | | ⬜ |
| TC-RTD-033 | PATCH price = 0 | 200 (giá 0 hợp lệ — không phải lỗi) | | ⬜ |
| TC-RTD-041 | PATCH inventory = 0 | 422 | | ⬜ |
| TC-RTD-043 | PATCH inventory = "năm phòng" | 422 | | ⬜ |

### Ảnh

| TC ID | Kịch bản | Kỳ vọng | Kết quả | Status |
|-------|----------|---------|---------|--------|
| TC-RTD-090 | Tạo phòng kèm `images: [path1, path2]` | Lưu đúng `sort_order` 0,1 | | ⬜ |
| TC-RTD-092 | Update với `images` mới | Xóa ảnh cũ, thay ảnh mới | | ⬜ |
| TC-RTD-093 | Xóa 1 ảnh giữa danh sách | Các ảnh còn lại tự sắp xếp lại `sort_order` liên tiếp | | ⬜ |
| TC-RTD-094 | Xóa ảnh ID không tồn tại | 404 | | ⬜ |

---

## 5. Lỗi phát hiện

Xem chi tiết tại `BugReport_RoomTypes_W6.md`. Điền lại sau khi chạy thật.

---

## 6. Checklist nghiệm thu module Room Types (tuần 6)

| # | Điều kiện | Trạng thái |
|---|-----------|------------|
| 1 | CRUD room_types lưu đúng dữ liệu, slug tự sinh đúng | ⬜ |
| 2 | Validation từ chối giá âm, sức chứa ≤0, số lượng ≤0 | ⬜ |
| 3 | Cập nhật giá riêng và số lượng riêng hoạt động độc lập, đúng API contract | ⬜ |
| 4 | **Soft-delete vs hidden hoạt động đúng theo trạng thái booking** (quan trọng nhất) | ⬜ |
| 5 | Khôi phục loại phòng đã xóa hoạt động đúng | ⬜ |
| 6 | Danh sách phòng theo từng hotel chính xác, không lẫn hotel khác | ⬜ |
| 7 | Ảnh được lưu, thay thế, xóa và tự sắp xếp lại đúng | ⬜ |
| 8 | Hoạt động ổn định với 20 loại phòng/hotel, response <500ms | ⬜ |
| 9 | Không còn lỗi Critical/High sau khi sửa và retest | ⬜ |

---

## 7. Việc cần làm tiếp

1. Chạy `php artisan test tests/Feature/RoomType/RoomTypeDataTest.php -v` trên máy có đủ `vendor/` và database test.
2. Điền kết quả pass/fail vào bảng mục 3 và 4.
3. Nếu có FAIL, ghi vào `BugReport_RoomTypes_W6.md`, gắn đúng người sửa (thường là BE2 — domain Hotel/Room).
4. Import `Homi_RoomTypes_Week6.postman_collection.json` vào Postman, chạy Collection Runner để có thêm minh chứng ảnh chụp màn hình.
5. Sau khi tất cả lỗi Critical/High đã sửa, đánh dấu nghiệm thu mục 6.