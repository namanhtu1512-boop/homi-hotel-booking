# Checklist bảo mật phiên đăng nhập — Homi Backend

**Phụ trách:** BE1 (Core Backend, Auth, RBAC)
**Cơ chế:** Laravel Sanctum — Personal Access Token (Bearer token), không dùng session/cookie cho API
**Cập nhật:** Tuần 4–6

---

## 1. Lưu trữ & truyền tải credential

| # | Hạng mục | Trạng thái | Ghi chú |
|---|---|---|---|
| 1.1 | Password hash bằng bcrypt (`Hash::make`), không lưu plaintext | ✅ | [User.php](../app/Models/User.php) cast `password => hashed` |
| 1.2 | Password không bao giờ trả về trong response | ✅ | `User::$hidden = ['password', 'remember_token']` — kiểm chứng bởi `RegisterTest::test_password_not_exposed_in_response`, `LoginTest::test_password_not_in_login_response` |
| 1.3 | Độ dài mật khẩu tối thiểu 6 ký tự | ✅ | `RegisterRequest`, `ChangePasswordRequest` — rule `min:6` |
| 1.4 | Token truyền qua header `Authorization: Bearer {token}`, không qua query string/URL | ✅ | Sanctum mặc định |
| 1.5 | HTTPS bắt buộc ở môi trường production | ⚠️ Cần xác nhận khi deploy | Không kiểm soát được ở tầng code, cần cấu hình ở web server/hosting (tuần 14–16) |

## 2. Xác thực đăng nhập

| # | Hạng mục | Trạng thái | Ghi chú |
|---|---|---|---|
| 2.1 | Sai email/sai password trả message **chung chung**, không tiết lộ email có tồn tại hay không | ✅ | `AuthController::login()` — "Email hoặc mật khẩu không đúng." cho cả 2 trường hợp |
| 2.2 | Kiểm tra `status=locked` **sau khi** xác thực password (tránh timing oracle/account enumeration qua thời gian phản hồi) | ✅ | Check password trước, check status sau, trong cùng 1 hàm `login()` |
| 2.3 | Tài khoản bị khóa không tạo được token mới | ✅ | `LoginTest::test_login_fails_when_account_is_locked`, status 403 |
| 2.4 | Tài khoản bị khóa giữa phiên (đã có token) vẫn bị chặn ở các request tiếp theo | ✅ | `RoleMiddleware` + `CheckActiveAccount` kiểm tra `status` mỗi request, không chỉ lúc login |
| 2.5 | Validate input email/password trước khi query DB | ✅ | `LoginRequest` |

## 3. Vòng đời token (Sanctum Personal Access Token)

| # | Hạng mục | Trạng thái | Ghi chú |
|---|---|---|---|
| 3.1 | Mỗi lần login/register tạo token mới, không tái sử dụng token cũ | ✅ | `$user->createToken('homi_token')` |
| 3.2 | Logout thu hồi đúng token hiện tại (không xóa toàn bộ token của user trên thiết bị khác) | ✅ | `$request->user()->currentAccessToken()->delete()` |
| 3.3 | Đổi mật khẩu thu hồi **toàn bộ** token đang có (buộc đăng nhập lại trên mọi thiết bị) | ✅ | `ChangePasswordRequest` flow — `$user->tokens()->delete()` |
| 3.4 | Token bị thu hồi/xóa khỏi DB thì request sau đó bị từ chối 401 | ✅ | `RbacTest::test_revoked_token_returns_401` |
| 3.5 | Token có thời hạn hết hạn tự động (`expiration`) | ❌ **Chưa cấu hình** | `config/sanctum.php` → `'expiration' => null` nghĩa là token **không bao giờ tự hết hạn**. Rủi ro: token bị lộ (ví dụ lưu trên thiết bị công cộng) sẽ tồn tại vĩnh viễn cho tới khi user chủ động đổi mật khẩu hoặc logout. **Khuyến nghị:** đặt `SANCTUM_EXPIRATION` (phút) trong `.env`, ví dụ `43200` (30 ngày), trước khi lên production — xem mục 5 |

## 4. Phân quyền theo phiên (RBAC)

| # | Hạng mục | Trạng thái | Ghi chú |
|---|---|---|---|
| 4.1 | Mọi route cần đăng nhập đều qua `auth:sanctum` | ✅ | [routes/api.php](../routes/api.php) |
| 4.2 | Role được set cứng `customer` khi đăng ký, người dùng không thể tự chọn role qua API | ✅ | `RbacTest::test_newly_registered_user_has_customer_role` |
| 4.3 | Đổi role/khóa tài khoản chỉ admin thực hiện được, qua API riêng có middleware `role:admin` | ✅ | `AdminUserController::toggleStatus` |
| 4.4 | Admin không thể tự khóa chính mình (tránh tự khóa toàn bộ quyền truy cập) | ✅ | `AdminUserController.php:65` |
| 4.5 | Request thiếu token / token sai → 401 thống nhất ở mọi route, không lộ stack trace | ✅ | `bootstrap/app.php` exception handler |
| 4.6 | Resource-level policy (Hotel, RoomType) tách biệt với role-level middleware | ✅ | [HotelPolicy](../app/Policies/HotelPolicy.php), [RoomTypePolicy](../app/Policies/RoomTypePolicy.php) — tuần 5–6 |

## 5. Khuyến nghị trước khi deploy (Tuần 14–16)

1. **Đặt thời hạn token** — thêm vào `.env`: `SANCTUM_EXPIRATION=43200` (30 ngày) hoặc theo chính sách nhóm thống nhất. Hiện tại `null` = không hết hạn.
2. **Rate limit đăng nhập** — hiện chưa thấy throttle riêng cho `/login`, `/register`. Nên thêm `throttle:login` (ví dụ 5 request/phút/IP) để chống brute-force, tận dụng `ErrorCode::TOO_MANY_REQUESTS` đã có sẵn handler.
3. **HTTPS only** — cấu hình `APP_URL` https, redirect HTTP→HTTPS ở web server.
4. **CORS** — kiểm tra `config/cors.php` chỉ cho phép domain frontend chính thức, không để `*` ở production.

---

## 6. Bằng chứng kiểm thử

Toàn bộ mục có trạng thái ✅ ở bảng 2–4 được xác minh bằng test tự động:

```
tests/Feature/Auth/RegisterTest.php  — 8 test
tests/Feature/Auth/LoginTest.php     — 6 test
tests/Feature/Auth/ProfileTest.php   — 11 test
tests/Feature/Auth/RbacTest.php      — 7 test
```

Chạy: `php artisan test tests/Feature/Auth`
