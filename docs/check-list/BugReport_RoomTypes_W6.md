# BUG REPORT - Module Room Types (Dữ liệu, Giá, Số lượng)
**Dự án:** Homi Hotel Booking
**Module:** Room Types — CRUD dữ liệu, validation, giá, số lượng, ảnh
**BE4 phụ trách:** [Tên thật của bạn]
**Tuần kiểm thử:** Tuần 6 (15/06 - 21/06/2026)
**Ngày tạo:** [Điền ngày bạn chạy test]
**Trạng thái tổng quan:** 🔄 Cập nhật sau khi chạy `php artisan test --filter=RoomTypeDataTest`

---

## Quy ước mức độ lỗi

| Mức | Định nghĩa |
|-----|------------|
| 🔴 Critical | Sai dữ liệu lưu DB, mất dữ liệu, lỗi bảo mật. Sửa ngay. |
| 🟠 High | Logic nghiệp vụ sai (vd: soft-delete xóa nhầm phòng đang có khách). Sửa trong tuần. |
| 🟡 Medium | Validation thiếu/sai, message không đúng chuẩn. Sửa trước nghiệm thu. |
| 🟢 Low | Cải thiện nhỏ, không ảnh hưởng nghiệp vụ. |

---

## Hướng dẫn dùng file này

File `RoomTypeDataTest.php` đã được viết sẵn 100% theo đúng code thật của RoomTypeService, RoomTypeController, RoomTypePolicy trong project (đọc trực tiếp từ source, không phải đoán). Sau khi bạn chạy:

```bash
php artisan test --filter=RoomTypeDataTest
```

- Nếu **tất cả PASS** → không cần ghi bug, xóa các dòng mẫu bên dưới, ghi "Không phát hiện lỗi tuần 6".
- Nếu có **FAIL** → copy message lỗi PHPUnit vào bảng dưới, đổi trạng thái thành Open.

---

## Mẫu bug thường gặp (điền lại nếu thực tế xảy ra)

### BUG-RT-001 (mẫu — chỉ điền nếu thực sự xảy ra)
| Trường | Nội dung |
|--------|----------|
| **Mã lỗi** | BUG-RT-001 |
| **Mức độ** | 🟠 High |
| **Chức năng** | DELETE /api/v1/admin/room-types/{id} |
| **Tiêu đề** | Loại phòng đang có booking pending vẫn bị soft-delete thay vì chuyển hidden |
| **Test case liên quan** | TC-RTD-061 |
| **Bước tái hiện** | 1. Tạo room_type. 2. Tạo booking + booking_item với status=pending cho room_type đó. 3. Admin gọi DELETE /admin/room-types/{id}. 4. Kiểm tra `deleted_at` và `status` trong DB. |
| **Kết quả thực tế** | `deleted_at` có giá trị (đã xóa mềm). |
| **Kết quả kỳ vọng** | `deleted_at` vẫn NULL, `status` chuyển thành `hidden`. |
| **Root Cause (dự đoán)** | `softDeleteOrDeactivate()` trong RoomTypeService chưa check đúng điều kiện `whereIn('status', ['pending','confirmed'])` trên bảng bookings liên kết qua booking_items. |
| **Người sửa** | BE3 (RoomTypeService thuộc domain Booking) hoặc BE2 (domain Hotel/Room) — xác nhận lại với nhóm ai phụ trách file `RoomTypeService.php`. |
| **Trạng thái** | 🟠 Open |

---

### BUG-RT-002 (mẫu)
| Trường | Nội dung |
|--------|----------|
| **Mã lỗi** | BUG-RT-002 |
| **Mức độ** | 🟡 Medium |
| **Chức năng** | POST /api/v1/admin/hotels/{hotelId}/room-types |
| **Tiêu đề** | Validation message field "price_per_night" không hiển thị tên tiếng Việt |
| **Test case liên quan** | TC-RTD-020 |
| **Kết quả thực tế** | `"The price per night field is required."` |
| **Kết quả kỳ vọng** | `"Trường giá theo đêm không được để trống."` |
| **Root Cause (dự đoán)** | `lang/vi/validation.php` chưa load đúng locale, hoặc `App::setLocale('vi')` chưa được gọi trong middleware/test env. |
| **Người sửa** | BE1 |
| **Trạng thái** | 🟡 Open |

---

## Bảng tổng hợp (điền sau khi chạy thật)

| Bug ID | Mức | Tiêu đề | TC liên quan | Trạng thái |
|--------|-----|---------|---------------|------------|
| | | | | |

| Mức độ | Tổng | Open | Fixed | Closed |
|--------|------|------|-------|--------|
| 🔴 Critical | | | | |
| 🟠 High | | | | |
| 🟡 Medium | | | | |
| 🟢 Low | | | | |

> ⚠️ Người phát hiện lỗi (BE4) không tự đóng lỗi. Sau khi người phụ trách sửa, BE4 chạy lại đúng test case đó và mới cập nhật trạng thái Closed.