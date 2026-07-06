# API Error Catalog — Homi Backend

**Phụ trách:** BE1 (Core Backend, Auth, RBAC, API Standard)
**Cập nhật:** Tuần 4–6
**Phạm vi:** Toàn bộ response lỗi của API `/api/v1/*`

---

## 1. Cấu trúc response lỗi chuẩn

Mọi lỗi trả về đều có dạng JSON sau (xem [app/Traits/ApiResponse.php](../app/Traits/ApiResponse.php) và [bootstrap/app.php](../bootstrap/app.php)):

```json
{
  "success": false,
  "message": "Mô tả lỗi bằng tiếng Việt.",
  "error_code": "VALIDATION_ERROR",
  "errors": { "field": ["chi tiết lỗi field này"] }
}
```

- `message` — luôn có, mô tả ngắn gọn bằng tiếng Việt, hiển thị được trực tiếp cho người dùng.
- `error_code` — có ở các lỗi hệ thống dùng chung (xem mục 2); một số lỗi nghiệp vụ cụ thể trả về **không kèm `error_code`** (xem mục 3) vì gắn liền với một API riêng lẻ.
- `errors` — chỉ xuất hiện ở lỗi `422 VALIDATION_ERROR`, là object `{field: [messages]}` theo chuẩn Laravel validator.

---

## 2. Mã lỗi hệ thống dùng chung ([App\Enums\ErrorCode](../app/Enums/ErrorCode.php))

Các lỗi này được xử lý tập trung tại `bootstrap/app.php` (exception handler) hoặc middleware, áp dụng cho **toàn bộ route**.

| error_code | HTTP Status | Khi nào xảy ra | Nguồn xử lý |
|---|---|---|---|
| `VALIDATION_ERROR` | 422 | Dữ liệu request không qua được rule validate (FormRequest hoặc `$request->validate()`) | `ValidationException` handler, `BaseFormRequest::failedValidation()` |
| `UNAUTHENTICATED` | 401 | Request không có token / token không hợp lệ / token đã bị thu hồi | `AuthenticationException` handler, `RoleMiddleware` |
| `UNAUTHORIZED` | 403 | Đã đăng nhập nhưng role không đủ quyền, hoặc Policy từ chối (`$this->authorize()`) | `AuthorizationException` handler, `RoleMiddleware`, `BaseFormRequest::failedAuthorization()` |
| `ACCOUNT_LOCKED` | 403 | Tài khoản có `status=locked` cố gắng truy cập route cần đăng nhập | `RoleMiddleware`, `CheckActiveAccount` |
| `NOT_FOUND` | 404 | Resource không tồn tại (`ModelNotFoundException`) hoặc route không tồn tại | `ModelNotFoundException` / `NotFoundHttpException` handler |
| `METHOD_NOT_ALLOWED` | 405 | Gọi sai HTTP method cho route tồn tại | `MethodNotAllowedHttpException` handler |
| `TOO_MANY_REQUESTS` | 429 | Vượt rate limit | `TooManyRequestsHttpException` handler |
| `SERVER_ERROR` | 500 (hoặc theo `HttpException::getStatusCode()`) | Lỗi không xác định / lỗi server / `abort()` với status khác | `HttpException` handler, fallback `Throwable` handler |

### Ví dụ response

**422 — Validation:**
```json
{
  "success": false,
  "message": "Dữ liệu không hợp lệ.",
  "error_code": "VALIDATION_ERROR",
  "errors": { "email": ["Email đã được sử dụng."] }
}
```

**401 — Chưa đăng nhập:**
```json
{
  "success": false,
  "message": "Bạn chưa đăng nhập.",
  "error_code": "UNAUTHENTICATED"
}
```

**403 — Không đủ quyền:**
```json
{
  "success": false,
  "message": "Bạn không có quyền thực hiện thao tác này.",
  "error_code": "UNAUTHORIZED"
}
```

**403 — Tài khoản bị khóa:**
```json
{
  "success": false,
  "message": "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.",
  "error_code": "ACCOUNT_LOCKED"
}
```

**404 — Không tìm thấy:**
```json
{
  "success": false,
  "message": "Không tìm thấy dữ liệu.",
  "error_code": "NOT_FOUND"
}
```

---

## 3. Lỗi nghiệp vụ cụ thể (không kèm `error_code`)

Các lỗi này dùng `$this->error($message, $status)` trực tiếp trong controller, áp dụng riêng cho 1 API — không có mã lỗi tái sử dụng vì ngữ cảnh hẹp.

| API | Status | Message | File |
|---|---|---|---|
| `POST /login` | 401 | "Email hoặc mật khẩu không đúng." | [AuthController.php:46](../app/Http/Controllers/Api/AuthController.php) |
| `POST /login` | 403 | "Tài khoản đã bị khóa." | [AuthController.php:50](../app/Http/Controllers/Api/AuthController.php) |
| `PUT /change-password` | 422 | "Mật khẩu hiện tại không đúng." | [AuthController.php:78](../app/Http/Controllers/Api/AuthController.php) |
| `PATCH /admin/users/{user}/toggle-status` | 422 | "Không thể khóa tài khoản của chính mình." | [AdminUserController.php:66](../app/Http/Controllers/Api/Admin/AdminUserController.php) |
| `DELETE /admin/hotels/{id}/images/{imageId}` | 404 | "Không tìm thấy ảnh." | [AdminHotelController.php:110](../app/Http/Controllers/Api/Admin/AdminHotelController.php) |
| `DELETE /admin/room-types/{id}/images/{imageId}` | 404 | "Không tìm thấy ảnh." | [AdminRoomTypeController.php:126](../app/Http/Controllers/Api/Admin/AdminRoomTypeController.php) |
| `POST /admin/hotels/{hotelId}/room-types` | 422 | "Không thể thêm phòng cho khách sạn đang bị ẩn." (field: `hotel_id`) | [RoomTypeService.php](../app/Services/RoomTypeService.php) — `assertHotelActive()` |
| `PATCH /admin/room-types/{id}/inventory`, `PUT /admin/room-types/{id}` | 422 | "Số lượng phòng phải lớn hơn hoặc bằng 1." (field: `total_rooms`) | [RoomTypeService.php](../app/Services/RoomTypeService.php) — `validateInventoryReduction()` |

> **Quy ước cho thành viên khác:** nếu lỗi nghiệp vụ chỉ xảy ra ở 1 API duy nhất, dùng `$this->error()` trực tiếp như trên, **không cần** thêm case mới vào `ErrorCode` enum. Chỉ thêm `ErrorCode` mới khi lỗi đó lặp lại ở ≥ 2 module khác nhau.

---

## 4. Validation message — quy ước tiếng Việt

Tất cả `FormRequest` kế thừa [BaseFormRequest](../app/Http/Requests/BaseFormRequest.php), khai báo message qua `messages()` và tên field tiếng Việt qua `attributes()`. Một số quy ước:

| Rule | Message mẫu |
|---|---|
| `required` | "{Trường} không được để trống." |
| `email` | "Email không đúng định dạng." |
| `unique` | "{Trường} đã được sử dụng." |
| `min` (password) | "Mật khẩu phải có ít nhất 6 ký tự." |
| `confirmed` | "Xác nhận mật khẩu không khớp." |

Nếu một `FormRequest` không khai báo `messages()`/`attributes()` riêng, Laravel sẽ rơi về message mặc định tiếng Anh — **cần tránh**, mọi FormRequest mới phải khai báo đủ 2 method này (hoặc dùng `attributes()` qua trait dùng chung, ví dụ [HasRoomTypeAttributes](../app/Http/Requests/RoomType/Concerns/HasRoomTypeAttributes.php)).

---

## 5. Checklist khi thêm lỗi mới

- [ ] Có cần `error_code` dùng chung không? (lặp lại ≥ 2 module → có, ngược lại dùng `$this->error()` trực tiếp)
- [ ] Message tiếng Việt, không lộ thông tin nhạy cảm (ví dụ: không nói rõ "email không tồn tại" khi login — chỉ nói chung "Email hoặc mật khẩu không đúng")
- [ ] Status code đúng chuẩn HTTP (422 validate, 401 chưa đăng nhập, 403 không đủ quyền, 404 không tìm thấy)
- [ ] Đã cập nhật bảng trong file này nếu thêm `ErrorCode` mới hoặc lỗi nghiệp vụ mới
