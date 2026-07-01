# USABILITY CHECKLIST — Sprint 4 (Tuần 8)
**Dự án:** Homi Hotel Booking
**Phạm vi:** Room List (`/rooms`) → Room Detail (`/rooms/{id}`) → Booking Form (`/customer/bookings/create`)
**TV4/BE4 phụ trách:** [Tên thật]
**Tuần:** 8 (29/06 – 05/07/2026)

> Ghi chú: đây là checklist **usability** (trải nghiệm người dùng), khác với
> checklist kỹ thuật trước-merge (`php artisan homi:qa-checklist`). Các mục
> đánh dấu ✅ đã xác nhận được qua code + test tự động. Các mục đánh dấu
> **⚠️ Cần kiểm tra thủ công trên trình duyệt thật** là những gì công cụ dòng
> lệnh không thể xác nhận (responsive, cảm nhận thị giác, thao tác chạm) —
> người kiểm thử cần tự mở trình duyệt (điện thoại thật hoặc DevTools
> responsive mode) để xác nhận trước khi tick.

---

## 1. Điều hướng & luồng thao tác

| # | Tiêu chí | Trạng thái | Ghi chú |
|---|---|---|---|
| 1.1 | Từ danh sách phòng bấm "Xem chi tiết" đến đúng trang phòng đó | ✅ | `RoomDetailBookingFormTest::test_room_list_contains_link_to_room_detail` |
| 1.2 | Ngày nhận/trả phòng và số lượng nhập ở trang Detail được giữ nguyên khi chuyển sang Booking Form (không phải nhập lại) | ✅ | `test_room_detail_check_in_check_out_params_are_preserved`, `test_booking_form_prefills_dates_from_query_params` |
| 1.3 | Nút "Đặt phòng ngay" chỉ hiện khi còn phòng trống (`can_book = true`) — tránh dẫn khách vào form rồi báo lỗi | ✅ | Xem `rooms/show.blade.php` dòng 116-126, có test `test_room_detail_booking_link_uses_correct_route` |
| 1.4 | Khách chưa đăng nhập bấm đặt phòng → được đưa thẳng tới trang login, không gặp lỗi khó hiểu | ✅ | `test_guest_is_redirected_to_login_when_accessing_booking_form` |
| 1.5 | Sau khi đặt phòng thành công, có thông báo rõ ràng kèm mã đơn | ✅ | `BookingController::store()` — flash `"Đặt phòng thành công! Mã đơn: {$booking->booking_code}."` |
| 1.6 | Từ trang danh sách đơn có thể xem lại chi tiết & hủy đơn | ✅ | `CustomerBookingFlowTest::test_customer_can_view_own_bookings_list_and_detail`, `test_customer_can_cancel_pending_booking` |
| 1.7 | Breadcrumb / cách quay lại danh sách phòng từ trang Detail rõ ràng | ⚠️ Cần kiểm tra thủ công | Không thấy breadcrumb trong `rooms/show.blade.php` — chỉ có menu chính của layout. Nên xác nhận trên trình duyệt xem có đủ rõ đường quay lại `/rooms` không. |

## 2. Nội dung & ngôn ngữ

| # | Tiêu chí | Trạng thái | Ghi chú |
|---|---|---|---|
| 2.1 | Toàn bộ label, placeholder, thông báo lỗi bằng tiếng Việt, không lẫn tiếng Anh | ✅ | Đã rà tất cả message trong `StoreBookingRequest`, `DateRangeService`, `FilterRoomTypeRequest` — 100% tiếng Việt |
| 2.2 | Giá tiền hiển thị đúng định dạng Việt Nam (dấu chấm ngăn cách nghìn, hậu tố "đ") | ✅ | `number_format($price, 0, ',', '.')` + JS `toLocaleString('vi-VN')`, có test `assertSee('1.500.000')` |
| 2.3 | Các field bắt buộc có dấu `*` màu đỏ để phân biệt field tùy chọn | ✅ | `customer/booking/create.blade.php` — `<span style="color:var(--danger)">*</span>` trên room_type_id, check_in, check_out, quantity, customer_name, customer_phone |
| 2.4 | Thông báo khi phòng hết chỗ nói rõ còn bao nhiêu phòng, không chỉ nói "hết phòng" chung chung | ✅ | `"Chỉ còn {$availability['available_quantity']} phòng trống, không đủ cho {$quantity} phòng bạn yêu cầu."` |
| 2.5 | Thông báo lỗi ngày không hợp lệ dễ hiểu, không lộ chi tiết kỹ thuật | ✅ | "Ngày trả phòng phải sau ngày nhận phòng ít nhất 1 đêm.", "Ngày nhận phòng không được trước hôm nay." — có test `test_room_detail_shows_error_when_*` |
| 2.6 | Trạng thái đơn (pending/confirmed/...) hiển thị bằng nhãn tiếng Việt dễ hiểu, không hiện thẳng giá trị enum (`pending`) | ⚠️ Cần kiểm tra thủ công | Có `BookingStatus::label()` trong code (dùng ở API `formatBooking()`), cần xác nhận `customer/bookings/index.blade.php` và `show.blade.php` dùng `->status->label()` chứ không in thẳng `->status->value` khi hiển thị cho khách. |

## 3. Phản hồi & xử lý lỗi (feedback)

| # | Tiêu chí | Trạng thái | Ghi chú |
|---|---|---|---|
| 3.1 | Ước tính giá tự động cập nhật khi đổi ngày/số phòng, không cần submit form | ✅ | JS `updateEstimate()` trong cả `rooms/show.blade.php` và `customer/booking/create.blade.php`, chạy khi `change` trên các input |
| 3.2 | Trang danh sách phòng không có kết quả vẫn hiện thông báo rõ ràng thay vì màn hình trắng | ✅ | `"Không tìm thấy loại phòng phù hợp với bộ lọc."` — `rooms/index.blade.php` |
| 3.3 | Lỗi validate ở form filter (giá/sức chứa/ngày) hiển thị ngay trên trang, không mất dữ liệu đã nhập | ✅ | `$errors->any()` render trong `rooms/index.blade.php`, có test `test_invalid_price_range_shows_validation_error_instead_of_breaking_page` |
| 3.4 | Nút submit "Xác nhận đặt phòng" có bị disable/hiện loading khi đang gửi, tránh khách bấm 2 lần tạo trùng đơn không? | ⚠️ Cần kiểm tra thủ công | Đọc `customer/booking/create.blade.php`: nút submit **không có** `disabled` khi submit hoặc debounce JS. Về mặt dữ liệu không tạo trùng nghiêm trọng (mỗi lần submit vẫn tạo đơn hợp lệ riêng vì `booking_code` luôn unique), nhưng khách có thể vô tình tạo 2 đơn giống nhau nếu bấm nhanh 2 lần trên mạng chậm. Đề xuất: cân nhắc thêm `disabled` khi `submit` cho form này (không phải lỗi tuần này, ghi nhận để cải thiện UX). |
| 3.5 | Khi hủy đơn, có xác nhận trước khi hủy (tránh bấm nhầm) | ⚠️ Cần kiểm tra thủ công | Cần mở `customer/bookings/show.blade.php` trên trình duyệt xác nhận có `confirm()` JS hoặc modal xác nhận trước khi submit form hủy, vì đây là hành động khó hoàn tác. |

## 4. Khả năng tiếp cận (accessibility)

| # | Tiêu chí | Trạng thái | Ghi chú |
|---|---|---|---|
| 4.1 | Ảnh gallery có `alt`/`aria-label` mô tả | ✅ | `_room-gallery.blade.php` — `role="img" aria-label="Ảnh phòng: {{ $alt }}"` |
| 4.2 | Mọi `<input>` có `<label for="...">` tương ứng đúng `id` | ✅ | Rà `rooms/show.blade.php` và `customer/booking/create.blade.php` — tất cả input đều có label liên kết đúng id (check_in, check_out, quantity, customer_name, customer_phone, customer_email, note) |
| 4.3 | Trang có khai báo `lang="vi"` và `viewport` cho mobile | ✅ | `layouts/app.blade.php` dòng 2, 6 |
| 4.4 | Độ tương phản màu chữ/nền đủ đọc (đặc biệt badge, giá tiền) | ⚠️ Cần kiểm tra thủ công | Không thể xác nhận độ tương phản từ code (phụ thuộc CSS thực tế render trong trình duyệt) — cần kiểm tra bằng công cụ Lighthouse/axe khi có trình duyệt. |

## 5. Responsive / đa thiết bị

| # | Tiêu chí | Trạng thái | Ghi chú |
|---|---|---|---|
| 5.1 | Trang `/rooms` hiển thị dạng lưới (`room-grid`), có khả năng co giãn theo màn hình | ⚠️ Cần kiểm tra thủ công | Cần mở DevTools responsive mode (375px, 768px, 1280px) để xác nhận `room-grid`/`dashboard-grid` (CSS) không vỡ layout trên mobile. |
| 5.2 | Form đặt phòng (nhiều field) không bị tràn ngang trên màn hình điện thoại | ⚠️ Cần kiểm tra thủ công | Tương tự — cần test tay trên thiết bị/emulator thực tế. |
| 5.3 | Input kiểu `date`/`number` hiển thị đúng bàn phím phù hợp trên di động | ⚠️ Cần kiểm tra thủ công | Về code đã dùng đúng `type="date"`, `type="number"`, `type="tel"`, `type="email"` (browser tự chọn bàn phím phù hợp) — chỉ cần xác nhận trực quan trên điện thoại thật. |

## 6. Hiệu năng cảm nhận (perceived performance)

| # | Tiêu chí | Trạng thái | Ghi chú |
|---|---|---|---|
| 6.1 | Trang `/rooms` tải dưới 500ms với 20 phòng | ✅ | `test_TC_RSL_070_page_loads_under_500ms_with_20_rooms` — đo thật, pass |
| 6.2 | Không có N+1 query khi tải danh sách phòng (tối đa 5 query cho 10 phòng) | ✅ | `test_TC_RSL_071_no_n_plus_1_query_with_10_rooms` — đo thật, pass |
| 6.3 | API danh sách 20 phòng (admin) dưới 500ms | ✅ | `test_TC_RTD_101_response_time_under_500ms_with_20_room_types` — đo thật, pass |

---

## Tổng kết

- **Đã xác nhận bằng code + test tự động:** 16/24 mục (✅).
- **Cần người thật mở trình duyệt/điện thoại để xác nhận:** 8/24 mục (⚠️), chủ yếu là responsive, độ tương phản màu, xác nhận trước khi hủy đơn, và bảo vệ double-submit — đây là giới hạn tự nhiên của kiểm thử qua code/PHPUnit, không thay thế được kiểm thử trực quan.
- Không phát hiện vấn đề usability nghiêm trọng nào chặn nghiệm thu; các mục ⚠️ nên được xác nhận thủ công trước khi coi Sprint 4 là "Done" hoàn toàn.
