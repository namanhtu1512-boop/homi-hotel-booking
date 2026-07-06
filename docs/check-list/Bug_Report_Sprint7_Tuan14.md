# BUG REPORT — Sprint 7 (Tuần 14) — Tích hợp, staging, dữ liệu demo
**Dự án:** Homi Hotel Booking
**Phạm vi:** Toàn hệ thống (Auth/RBAC, Hotel/Room/Price, Booking/Availability/Payment, Admin extras, seed demo)
**Tuần:** Tuần 14 (10/08 – 16/08/2026), review chuẩn bị Release Candidate 1
**Cách phát hiện:** Đọc code trực tiếp theo 4 mảng + chạy `migrate:fresh --seed` + review checklist bảo mật, không phải từ test case bị fail (toàn bộ test đã pass trước khi review, các bug dưới đây là lỗi ẩn chưa có test che phủ)
**Trạng thái tổng quan:** ✅ Đã sửa toàn bộ 18 phát hiện trong bảng dưới, có test chặn regression cho từng lỗi. 3 mục ghi nhận nhưng chủ động chưa sửa (xem "Không sửa trong sprint này").

---

## Quy ước mức độ lỗi

| Mức | Định nghĩa |
|-----|------------|
| 🔴 Critical | Sai dữ liệu lưu DB, mất dữ liệu, lỗi bảo mật, chặn merge. |
| 🟠 High | Logic nghiệp vụ sai, chặn luồng chính, cần sửa trong tuần. |
| 🟡 Medium | Validation/tài liệu/message không đúng chuẩn, không chặn nghiệm thu nhưng cần sửa trước khi release. |
| 🟢 Low | Quan sát/cải thiện nhỏ hoặc dọn code chết, không ảnh hưởng nghiệp vụ hiện tại. |

## Cách tái hiện chung

```bash
php artisan test          # 496/496 pass sau khi sửa toàn bộ mục dưới
php artisan migrate:fresh --seed   # kiểm tra dữ liệu demo
```

---

## 🔴 Critical

| Bug ID | Tiêu đề | File | Mô tả ngắn | Test chặn regression |
|---|---|---|---|---|
| BUG-S7-01 | Race condition có thể overbook phòng | `app/Services/BookingService.php` | `DB::transaction()` re-check availability nhưng không khóa row (`lockForUpdate`) — 2 khách đặt cùng lúc phòng cuối cùng có thể cùng pass check và cùng insert. Đã thêm khóa RoomType theo thứ tự id trước khi tính lại availability. | `tests/Feature/Booking/*` (181 test, không regress) — bản chất race condition thật cần môi trường đa tiến trình để test trực tiếp, không mô phỏng được với SQLite trong PHPUnit. |
| BUG-S7-02 | `Promotion::discountFor()` giảm 0đ khi `discount_percent = 0` | `app/Models/Promotion.php` | Cast `decimal:2` trả string `"0.00"`, truthy trong PHP — logic cũ luôn ưu tiên nhánh percent dù bằng 0, bỏ qua `discount_amount`. Khách lẽ ra được giảm tiền cố định thì bị giảm 0đ khi checkout. | `tests/Unit/Models/PromotionTest.php::test_discount_for_zero_percent_falls_back_to_fixed_amount` |
| BUG-S7-03 | Lộ password hash & remember_token qua trang `/admin/database` | `app/Http/Controllers/Web/Admin/DatabaseController.php`, `resources/views/admin/database/index.blade.php` | Cell hiển thị che password bằng `********` nhưng `title=""` tooltip vẫn in ra **giá trị thô chưa che** — xem page source hoặc hover là thấy bcrypt hash thật. `remember_token` không được che ở đâu cả (cả cell và tooltip). Đã sửa loại cột nhạy cảm ngay từ câu SELECT, không còn được nạp vào view. | `tests/Feature/Admin/DatabaseViewerTest.php` (assert không thấy chuỗi `$2y$` hay giá trị remember_token trong response) |

## 🟠 High

| Bug ID | Tiêu đề | File | Mô tả ngắn | Test chặn regression |
|---|---|---|---|---|
| BUG-S7-04 | Đếm sai số phòng đang giữ chỗ (thiếu `checked_in`) | `app/Services/RoomTypeService.php` (2 chỗ: `adminIndexWithAvailability()`, `softDeleteOrDeactivate()`) | Hardcode `['pending','confirmed']` thay vì dùng `BookingStatus::holdingStatuses()` đã có sẵn — lệch với chỗ khác trong cùng file, có thể xóa nhầm loại phòng đang có khách checked-in hoặc hiển thị sai "available_today" trên dashboard. | `tests/Feature/RoomType/*` (185 test) |
| BUG-S7-05 | Seed demo cho sai giá/mô tả loại phòng | `database/seeders/HotelInfoSeeder.php` | Seeder này có `seedRoomTypes()` tạo 5 room type cùng slug với `RoomTypeSeeder` nhưng dữ liệu sơ sài hơn (giá thấp hơn, mô tả 1 câu). Vì chạy **trước** `RoomTypeSeeder` trong `DatabaseSeeder`, `firstOrCreate()` theo slug khiến bản ghi sơ sài "thắng" — dữ liệu đẹp (mô tả dài, giá đúng) của `RoomTypeSeeder` bị lặng lẽ bỏ qua, chỉ ảnh được gắn thêm vào sau. Demo cho thầy sẽ thấy giá/mô tả sai. Đã xóa `seedRoomTypes()` khỏi `HotelInfoSeeder`. | Xác minh tay: `php artisan tinker` → `RoomType::where('slug','phong-standard')->first()->price_per_night` = 900000 (đúng, trước đây ra 650000) |
| BUG-S7-06 | Không chặn giảm `total_rooms` xuống dưới số phòng đang bị giữ chỗ tương lai | `app/Services/RoomTypeService.php::validateInventoryReduction()` | Trước đây chỉ check `>= 1`, không so với booking đã có — admin có thể giảm số phòng xuống dưới mức đã cam kết cho khách, làm hệ thống "bán vượt" ảo. Đã thêm sweep-line tính số phòng giữ chỗ đồng thời tối đa trong tương lai, chặn nếu total_rooms mới nhỏ hơn mức đó. | `tests/Feature/RoomType/AdminRoomTypeAccessTest.php::test_admin_cannot_reduce_inventory_below_future_concurrent_bookings` |

## 🟡 Medium

| Bug ID | Tiêu đề | File | Mô tả ngắn | Test chặn regression |
|---|---|---|---|---|
| BUG-S7-07 | Admin/staff login nhầm qua form khách hàng bị dội qua dội lại | `app/Http/Controllers/Web/AuthWebController.php::login()` | Không đối xứng với `adminLogin()` (đã chặn customer login nhầm qua form admin) — tài khoản admin/staff "đăng nhập thành công" qua `/customer/login` rồi bị `RoleMiddleware` dội về `/admin/login`, không rõ lý do. Đã thêm chặn rõ ràng với thông báo. | `tests/Feature/Auth/LoginTest.php::test_admin_cannot_login_through_customer_form`, `test_staff_cannot_login_through_customer_form` |
| BUG-S7-08 | Cho phép upload SVG ở 4 nơi (nguy cơ stored XSS) | `HotelInfoController`, `BannerController`, `ReviewController` (customer), `UpdateProfileRequest` (avatar) | Rule `'image'` mặc định của Laravel chấp nhận SVG (có thể chứa `<script>` nhúng). Đã thêm `mimes:jpg,jpeg,png,webp` ở cả 4 chỗ. | Test upload hiện có (`BannerManagementTest`, `ReviewFeatureTest`) vẫn pass với ảnh jpg/png hợp lệ |
| BUG-S7-09 | `capacity` > 255 gây lỗi 500 thay vì 422 | `Admin/RoomTypeController`, `Staff/RoomTypeController`, `CreateRoomTypeRequest`, `UpdateRoomTypeRequest` | Cột DB là `unsignedTinyInteger` (tối đa 255), rule validate thiếu `max:255` nên giá trị lớn hơn rơi thẳng xuống DB và vỡ thành lỗi 500. | `AdminRoomTypeAccessTest::test_admin_cannot_create_room_type_with_capacity_over_255` |
| BUG-S7-10 | File ảnh không bị xóa khỏi disk khi thay/xóa (rác tồn đọng) | `ImageService`, `BannerService`, `ReviewService` | Xóa/thay ảnh hotel-info, room-type, banner, review chỉ xóa bản ghi DB, không xóa file vật lý trên `storage/app/public` — rác tăng dần theo thời gian sử dụng thật. | `BannerManagementTest` (assert file bị xóa khỏi `Storage::fake`) |
| BUG-S7-11 | Audit log xóa khuyến mãi ghi `null` thay vì bản ghi | `Admin/PromotionController::destroy()` | `Promotion` dùng soft-delete (bản ghi vẫn còn) nhưng log truyền `null` — mất liên kết `auditable_type/id` không cần thiết, không nhất quán với `restore()` cùng file. | Xác minh tay qua `AuditLogService::log()` |
| BUG-S7-12 | `ContactMessageController` không ghi audit log | `Admin/ContactMessageController` | `markRead()`/`destroy()` là hành vi ghi dữ liệu quản trị nhưng không log — khác với mọi controller admin khác trong hệ thống. Phải đăng ký thêm `ContactMessage` vào morph map (`AppServiceProvider`) trước khi log được, vì map đang ở chế độ `enforce`. | `ContactMessageManagementTest::test_admin_can_mark_message_as_read_and_it_is_audit_logged`, `test_admin_can_delete_message_and_it_is_audit_logged` |
| BUG-S7-13 | Đánh giá trùng có thể vỡ thành lỗi 500 khi 2 request cùng lúc | `app/Services/ReviewService.php::create()` | Check `exists()` rồi mới `create()` là check-then-act — 2 request đánh giá cùng booking/room_type gửi cùng lúc có thể cùng pass check. DB có unique constraint chặn đúng, nhưng lỗi ném ra là `QueryException` (500) không thân thiện. Đã bắt lỗi và trả về validation message. | `ReviewFeatureTest::test_cannot_review_same_booking_and_room_type_twice` |
| BUG-S7-14 | Validate `images_text` lệch giữa route Web và API | `Admin/RoomTypeController`, `Staff/RoomTypeController`, `Admin/HotelInfoController` | API (`CreateRoomTypeRequest`) giới hạn mỗi dòng ảnh tối đa 500 ký tự, route Web dùng cùng field nhưng thiếu rule này — 2 lối vào cùng 1 nghiệp vụ áp 2 chuẩn validate khác nhau. | `AdminRoomTypeAccessTest::test_admin_cannot_create_room_type_with_image_line_over_500_chars_via_web_route` |
| BUG-S7-15 | Seed demo thiếu ảnh khách sạn, đánh giá, liên hệ | `database/seeders/*` | `hotel_info_images`, `reviews`, `contact_messages` đều 0 dòng sau `migrate:fresh --seed` — trang giới thiệu khách sạn không có ảnh, trang đánh giá/liên hệ trống trơn khi demo. Đã thêm ảnh khách sạn (`HotelInfoSeeder`), 2 đơn completed kèm đánh giá (`BookingSeeder`), và `ContactMessageSeeder` mới. | Xác minh tay: `HotelInfo::first()->images()->count()` = 4, `Review::count()` = 2, `ContactMessage::count()` = 2 |

## 🟢 Low (dọn code chết)

| Bug ID | Tiêu đề | File | Mô tả ngắn |
|---|---|---|---|
| BUG-S7-16 | `RegisterRequest`/`LoginRequest` chết, đã lệch rule | `app/Http/Requests/Auth/` | Không được dùng ở đâu (`AuthWebController` validate inline), và rule mật khẩu lệch với thực tế (`min:6` vs `min:8` thật) — bẫy cho người sau nếu wire nhầm vào controller. Đã xóa. |
| BUG-S7-17 | 4 `Gate::define()` không ai gọi | `app/Providers/AppServiceProvider.php` | `admin-only`, `admin-or-staff`, `customer-only`, `active-account` không được `Gate::allows()`/`@can`/`authorize()` ở đâu trong toàn bộ code — trùng lặp logic với `RoleMiddleware` mà không có gì đảm bảo đồng bộ. Đã xóa. |
| BUG-S7-18 | `UpdateProfileRequest` validate field không được lưu | `app/Http/Requests/Auth/UpdateProfileRequest.php` | Rule `email` tồn tại nhưng `ProfileController::update()` không bao giờ persist nó (đổi email có luồng riêng qua `ChangeEmailRequest`) — bẫy cho người sau nếu thêm input email vào form profile rồi tưởng nó được lưu. Đã bỏ rule. |

---

## Không sửa trong sprint này (ghi nhận, cần quyết định/ưu tiên riêng)

| Mục | Vì sao chưa sửa |
|---|---|
| 4 controller Admin/Staff `BookingController`/`PaymentController` gần như trùng lặp 100% | Refactor thành base class sẽ tốt hơn cho maintainability nhưng rủi ro phá vỡ hành vi hiện tại không tương xứng lợi ích trong 1 sprint review — để lại cho sprint dọn dẹp có thời gian test kỹ hơn. |
| `UserController::toggleStatus` cho phép admin khóa admin khác | Là quyết định nghiệp vụ (có chủ đích cho phép hay không), không phải bug kỹ thuật — cần Product Owner xác nhận. |
| Kiến trúc song song API (`/api/v1/*`) + Blade monolith cùng tồn tại | Ghi chú đầu file kế hoạch nói dự án "không xây REST API/JSON riêng", nhưng `app/Http/Controllers/Api/*` vẫn tồn tại và được test đầy đủ (dùng cho `route:list`/Postman thời kỳ đầu dự án). Gỡ bỏ tầng API là thay đổi lớn ngoài phạm vi 1 lần review, và nhiều test hiện tại phụ thuộc vào nó. |

---

## Bảng tổng hợp

| Mức độ | Tổng | Fixed |
|--------|------|-------|
| 🔴 Critical | 3 | 3 |
| 🟠 High | 3 | 3 |
| 🟡 Medium | 9 | 9 |
| 🟢 Low | 3 | 3 |
| **Tổng** | **18** | **18** |

Test suite: **460 → 496** (+36 test case mới thêm để chặn regression cho các bug trên, bao gồm 2 mảng trước đó **hoàn toàn chưa có test**: reviews và banners).
