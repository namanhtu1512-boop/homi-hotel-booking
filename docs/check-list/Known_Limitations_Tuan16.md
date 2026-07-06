# Known Limitations — Tuần 16 (bản gộp cuối cùng)

Gom lại toàn bộ giới hạn/việc chưa làm đã ghi nhận rải rác trong quá trình
làm dự án thành 1 danh sách duy nhất, để chủ động trình bày khi bảo vệ thay
vì để thầy tự phát hiện. Không phải bug — đều là quyết định phạm vi có chủ
đích hoặc đánh đổi thời gian đã biết trước.

> **Cập nhật 2026-07-06:** sau khi tài liệu này chốt lần đầu, nhóm vẫn còn
> thời gian nên đã làm thêm module dịch vụ, giá theo mùa/cuối tuần + phụ
> thu trẻ em, khuyến mãi stack nhiều mã, giữ chỗ tạm thời (room hold),
> check-in/check-out thật + housekeeping, đặt đoàn/nhóm, hóa đơn nội bộ —
> các mục tương ứng đã được xóa khỏi bảng dưới vì không còn là giới hạn.
> Chi tiết đầy đủ xem [`RELEASE_NOTE.md`](../../RELEASE_NOTE.md).
>
> **Cập nhật thêm:** bộ test tự động (PHPUnit/Pest) và toàn bộ tài liệu
> test-case/test-evidence/bug-report đã được gỡ bỏ khỏi dự án theo quyết
> định của nhóm — các câu trong bảng dưới nhắc tới "có test"/CI chạy test
> chỉ còn đúng ở thời điểm ghi nhận ban đầu, không còn phản ánh trạng thái
> hiện tại của repo.

## Nghiệp vụ / tính năng

| Giới hạn | Vì sao chấp nhận được |
|---|---|
| Thanh toán chỉ mô phỏng (không nối VNPay/Momo/Stripe thật) | Đúng phạm vi đề tài ("thanh toán mô phỏng"), có state machine đầy đủ (unpaid/pending/deposit_paid/paid/refunded) và test kỹ. |
| Email không gửi thật (`MAIL_MAILER=log`) | Không có luồng nghiệp vụ nào phụ thuộc email (xác nhận đơn hiển thị ngay trên web, không qua email). |
| Không có CAPTCHA ở đăng ký/liên hệ/đánh giá | Đã bù bằng rate-limit (5 lần/phút) ở các form nhạy cảm — giảm rủi ro spam/brute-force ở mức chấp nhận được cho quy mô đồ án. |
| Route `/admin/reports` (kế hoạch gốc liệt kê riêng) không tồn tại tách biệt | Toàn bộ số liệu báo cáo đã gộp vào `/admin/dashboard` (doanh thu, tỷ lệ hủy, lấp đầy) — không thiếu chức năng, chỉ khác tên route so với bảng route dự kiến ban đầu. |
| Không có màn tạo Amenity (tiện ích) mới qua UI | 10 tiện ích cố định từ seeder, admin chỉ chọn/bỏ chọn qua checkbox ở trang hotel-info — đủ dùng cho 1 khách sạn, không cần CRUD riêng. |

## Kỹ thuật / kiến trúc (không sửa trong đợt review vừa qua)

| Giới hạn | Lý do |
|---|---|
| Song song `/api/v1/*` (REST API) và Blade monolith | Tàn dư giai đoạn đầu dự án, mâu thuẫn với ghi chú kiến trúc trong kế hoạch — nhưng đã hoàn thiện đầy đủ (không còn stub) và có test, không gây rủi ro chức năng. Gỡ bỏ hẳn là thay đổi lớn ngoài phạm vi. |
| 4 controller Admin/Staff `BookingController`/`PaymentController` trùng lặp gần như 100% | Refactor thành base class tốt hơn cho bảo trì nhưng rủi ro phá vỡ hành vi hiện tại không tương xứng lợi ích trong thời gian còn lại. |
| `UserController::toggleStatus` cho phép admin khóa admin khác | Là quyết định nghiệp vụ (có chủ đích cho phép hay không), chưa xin ý kiến Product Owner để khóa cứng lại. |
| `PaymentStatus::FAILED` chưa có luồng thật sử dụng | Scaffolding sẵn cho xử lý thanh toán thất bại thật — chưa cần thiết vì luồng hiện tại chỉ dừng ở xác nhận/hoàn thành đơn. (`BookingStatus::CHECKED_IN/CHECKED_OUT` nay đã dùng thật — xem module lễ tân/housekeeping.) |
| Không CI/CD deploy tự động, không monitoring lỗi thật (Sentry...) | Chi phí hạ tầng ngoài phạm vi đồ án sinh viên. |

## Vận hành / hạ tầng

| Giới hạn | Phương án |
|---|---|
| Chưa deploy lên staging thật | Phương án hợp lệ theo tiêu chí nghiệm thu: chạy local ổn định + hướng dẫn cài đặt rõ ràng (`README.md`, `Staging_Checklist_Tuan14.md`). |
| Chưa có file backup DB đóng gói sẵn trong repo | Lệnh backup (`php artisan schema:dump` hoặc `mysqldump`) đã có sẵn và test chạy được — xem `DB_Checklist_Tuan16.md`, chạy ngay trước khi nộp/deploy để backup luôn là bản mới nhất. |
