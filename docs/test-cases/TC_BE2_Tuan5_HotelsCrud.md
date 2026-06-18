# Tài liệu Test Case — CRUD Khách sạn (Admin)
**Module:** Quản lý khách sạn (Admin) — thêm, sửa, xóa mềm, ẩn/hiện, xem chi tiết
**Phụ trách:** BE2
**Sprint:** Tuần 5 (08/06 – 14/06/2026)
**Môi trường:** Local — `http://127.0.0.1:8000/api/v1`
**Công cụ:** Postman + PHPUnit (`tests/Feature/Hotel/AdminHotelCrudTest.php`)
**Người tạo:** BE2
**Ngày tạo:** 18/06/2026

> Bộ test case này bổ sung cho `AdminHotelAccessTest` (đã có ở tuần 5, do BE1 viết — chỉ kiểm tra phân quyền).
> Phạm vi ở đây là **tính đúng đắn dữ liệu**: thiếu trường bắt buộc, trùng tên, ảnh lỗi, xóa mềm/khôi phục.
> Toàn bộ test case bên dưới đã được tự động hóa bằng PHPUnit và **pass 16/16**.

---

## Điều kiện tiên quyết chung

| Tài khoản | Email | Mật khẩu | Role | Trạng thái |
|-----------|-------|-----------|------|------------|
| Admin Demo | admin@homi.test | 123456 | admin | active |

> Trước mỗi nhóm test, đăng nhập lấy Bearer Token của tài khoản admin.
> Endpoint đăng nhập: `POST /api/v1/login` → lấy `token` từ response.

---

## Nhóm 1 — Thiếu trường bắt buộc

### TC-HCRUD-001 — Tạo khách sạn thiếu `name`

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-001 |
| **Dữ liệu đầu vào** | `POST /admin/hotels` body thiếu `name` |
| **Kết quả mong đợi** | HTTP 422 — `errors.name` có thông báo "Trường tên khách sạn là bắt buộc." |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-002 — Tạo khách sạn thiếu `city`

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-002 |
| **Dữ liệu đầu vào** | `POST /admin/hotels` body thiếu `city` |
| **Kết quả mong đợi** | HTTP 422 — `errors.city` |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-003 — Tạo khách sạn thiếu `address`

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-003 |
| **Dữ liệu đầu vào** | `POST /admin/hotels` body thiếu `address` |
| **Kết quả mong đợi** | HTTP 422 — `errors.address` |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-005 — `star_rating` ngoài khoảng 1–5

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-005 |
| **Dữ liệu đầu vào** | `POST /admin/hotels` với `star_rating = 6` |
| **Kết quả mong đợi** | HTTP 422 — `errors.star_rating` |
| **Trạng thái** | ✅ Pass |

---

## Nhóm 2 — Trùng tên khách sạn

### TC-HCRUD-004 — Tạo 2 khách sạn cùng tên ở 2 thành phố khác nhau

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-004 |
| **Mục tiêu** | Xác nhận hệ thống cho phép 2 khách sạn trùng tên (vd: cùng thương hiệu, khác thành phố) nhưng vẫn đảm bảo `slug` duy nhất trong DB |
| **Dữ liệu đầu vào** | Tạo "Homi Sài Gòn" tại Hà Nội, sau đó tạo lại "Homi Sài Gòn" tại TP Hồ Chí Minh |
| **Kết quả mong đợi** | Cả 2 request đều trả 201 — `slug` của 2 bản ghi khác nhau (vd: `homi-sai-gon`, `homi-sai-gon-2`) |
| **Ghi chú** | Trước khi sửa, trường hợp này gây lỗi 500 do vi phạm ràng buộc `UNIQUE(slug)`. Đã vá tại `HotelService::uniqueSlug()`. |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-010 — Cập nhật tên khách sạn trùng với khách sạn khác

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-010 |
| **Dữ liệu đầu vào** | `PUT /admin/hotels/{id}` đổi `name` thành tên đã tồn tại ở khách sạn khác |
| **Kết quả mong đợi** | HTTP 200 — cập nhật thành công — `slug` mới không trùng `slug` của khách sạn kia |
| **Trạng thái** | ✅ Pass |

---

## Nhóm 3 — Ảnh lỗi

### TC-HCRUD-006 — Phần tử `images` không phải chuỗi

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-006 |
| **Dữ liệu đầu vào** | `POST /admin/hotels` với `images: [12345]` |
| **Kết quả mong đợi** | HTTP 422 — `errors."images.0"` |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-007 — Đường dẫn ảnh quá dài (> 500 ký tự)

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-007 |
| **Dữ liệu đầu vào** | `POST /admin/hotels` với `images: ["a...a"]` (501 ký tự) |
| **Kết quả mong đợi** | HTTP 422 — `errors."images.0"` |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-008 — `amenity_ids` chứa ID không tồn tại

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-008 |
| **Dữ liệu đầu vào** | `POST /admin/hotels` với `amenity_ids: [99999]` |
| **Kết quả mong đợi** | HTTP 422 — `errors."amenity_ids.0"` |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-009 — Tạo khách sạn hợp lệ kèm ảnh + tiện ích

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-009 |
| **Dữ liệu đầu vào** | `POST /admin/hotels` với `images` và `amenity_ids` hợp lệ |
| **Kết quả mong đợi** | HTTP 201 — bảng `hotel_images` có đủ ảnh — bảng `hotel_amenity` có đúng liên kết |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-011 — Cập nhật thay toàn bộ ảnh (replace)

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-011 |
| **Dữ liệu đầu vào** | `PUT /admin/hotels/{id}` với `images` mới, khách sạn đã có ảnh cũ |
| **Kết quả mong đợi** | HTTP 200 — ảnh cũ bị xóa khỏi `hotel_images`, chỉ còn ảnh mới |
| **Trạng thái** | ✅ Pass |

---

## Nhóm 4 — Xóa mềm / Khôi phục

### TC-HCRUD-012 — Khách sạn xóa mềm biến mất khỏi danh sách public

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-012 |
| **Dữ liệu đầu vào** | `DELETE /admin/hotels/{id}` rồi `GET /hotels` (public) |
| **Kết quả mong đợi** | Khách sạn không xuất hiện trong danh sách public |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-013 — Xem chi tiết public khách sạn đã xóa mềm

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-013 |
| **Dữ liệu đầu vào** | `GET /hotels/{id}` (public) sau khi đã xóa mềm |
| **Kết quả mong đợi** | HTTP 404 |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-014 — Khôi phục khách sạn đã xóa mềm

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-014 |
| **Dữ liệu đầu vào** | `POST /admin/hotels/{id}/restore` sau khi đã xóa mềm |
| **Kết quả mong đợi** | HTTP 200 — khách sạn xuất hiện lại ở `GET /hotels/{id}` public |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-015 — Xóa khách sạn không tồn tại

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-015 |
| **Dữ liệu đầu vào** | `DELETE /admin/hotels/999999` |
| **Kết quả mong đợi** | HTTP 404 |
| **Trạng thái** | ✅ Pass |

### TC-HCRUD-016 — Ẩn khách sạn (toggle-status) khỏi public

| Trường | Nội dung |
|--------|----------|
| **ID** | TC-HCRUD-016 |
| **Dữ liệu đầu vào** | `PATCH /admin/hotels/{id}/toggle-status` (active → hidden) rồi `GET /hotels` public |
| **Kết quả mong đợi** | `status = hidden` — khách sạn không còn trong danh sách public |
| **Trạng thái** | ✅ Pass |

---

## Tổng hợp

| ID | Chức năng | Nhóm | Trạng thái |
|----|-----------|-------|------------|
| TC-HCRUD-001 | Thiếu `name` → 422 | Thiếu trường | ✅ |
| TC-HCRUD-002 | Thiếu `city` → 422 | Thiếu trường | ✅ |
| TC-HCRUD-003 | Thiếu `address` → 422 | Thiếu trường | ✅ |
| TC-HCRUD-005 | `star_rating` ngoài khoảng → 422 | Thiếu trường | ✅ |
| TC-HCRUD-004 | Trùng tên, 2 thành phố → slug khác nhau | Trùng tên | ✅ |
| TC-HCRUD-010 | Update trùng tên → slug khác | Trùng tên | ✅ |
| TC-HCRUD-006 | `images.0` không phải string → 422 | Ảnh lỗi | ✅ |
| TC-HCRUD-007 | `images.0` quá dài → 422 | Ảnh lỗi | ✅ |
| TC-HCRUD-008 | `amenity_ids.0` không tồn tại → 422 | Ảnh lỗi/tiện ích | ✅ |
| TC-HCRUD-009 | Tạo hợp lệ kèm ảnh + tiện ích | Ảnh lỗi/tiện ích | ✅ |
| TC-HCRUD-011 | Update thay toàn bộ ảnh | Ảnh lỗi/tiện ích | ✅ |
| TC-HCRUD-012 | Xóa mềm → ẩn khỏi public list | Xóa mềm | ✅ |
| TC-HCRUD-013 | Xóa mềm → public detail 404 | Xóa mềm | ✅ |
| TC-HCRUD-014 | Khôi phục → hiện lại public | Xóa mềm | ✅ |
| TC-HCRUD-015 | Xóa ID không tồn tại → 404 | Xóa mềm | ✅ |
| TC-HCRUD-016 | Toggle status → ẩn khỏi public | Xóa mềm | ✅ |

**Tổng:** 16 test case &nbsp;|&nbsp; **Pass:** 16 &nbsp;|&nbsp; **Fail:** 0

---

## Bug đã phát hiện và sửa trong tuần 5

| Mã lỗi | Mô tả | Mức độ | Trạng thái |
|--------|-------|--------|------------|
| BUG-H-001 | Tạo/sửa khách sạn trùng tên gây lỗi 500 (vi phạm `UNIQUE(slug)`) thay vì xử lý hợp lý | High | Đã sửa — `HotelService::uniqueSlug()` tự sinh hậu tố `-2`, `-3`... khi slug trùng |
| BUG-H-002 | `phpunit.xml` cấu hình coverage report tự động khiến `php artisan test` / `vendor/bin/phpunit` không chạy được (không có Xdebug/PCOV) trên máy dev | Critical (chặn toàn bộ kiểm thử) | Đã sửa — bỏ khối `<coverage><report>` mặc định, coverage chỉ chạy khi gọi cờ `--coverage-*` rõ ràng |
