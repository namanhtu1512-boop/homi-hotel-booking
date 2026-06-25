<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * BE4 — QA checklist trước khi merge PR.
 * Chạy: php artisan homi:qa-checklist
 */
class QaChecklistCommand extends Command
{
    protected $signature   = 'homi:qa-checklist {--module= : Tên module cần kiểm tra (auth|hotel|room|booking|payment)}';
    protected $description = 'Hiển thị QA checklist chuẩn trước khi merge PR vào main';

    private array $commonChecklist = [
        'Code & Review' => [
            'Branch đặt tên đúng quy tắc: feature/xxx, fix/xxx, hotfix/xxx',
            'Không commit trực tiếp vào main hoặc develop',
            'PR có mô tả rõ thay đổi gì và tại sao',
            'Không có debug log, dd(), dump(), var_dump() còn sót',
            'Không có commented-out code không cần thiết',
            'Không trả về dữ liệu nhạy cảm: password, token trong response',
        ],
        'API & Validation' => [
            'Tất cả route mới được đăng ký trong routes/api.php',
            'FormRequest validation đã có rule cho tất cả field bắt buộc',
            'Message validation bằng tiếng Việt (messages() method)',
            'Response dùng chuẩn ApiResponse trait: success, message, data, error_code',
            'HTTP status code đúng: 200/201 thành công, 422 validation, 401/403 phân quyền, 404 không tìm thấy',
            'Pagination trả đúng cấu trúc: data, meta (total, per_page, current_page...)',
        ],
        'Database & Model' => [
            'Migration không đặt tên trùng',
            'Foreign key có index phù hợp',
            'Soft delete ($table->softDeletes()) nếu cần khôi phục',
            'Model có $fillable đầy đủ, không dùng $guarded = []',
            '$casts đúng type (date, decimal, enum)',
            'Relationship được khai báo đúng chiều (belongsTo, hasMany...)',
        ],
        'Security' => [
            'Route admin/staff được bảo vệ bởi middleware role:admin,staff',
            'Route customer được bảo vệ bởi middleware role:customer',
            'Customer không xem/sửa được dữ liệu của customer khác',
            'Không có SQL injection (dùng Eloquent/Query Builder, không raw query unsanitized)',
            'Upload file kiểm tra mimetype, size, không lưu trực tiếp tên file gốc',
        ],
        'Test' => [
            'Có test case cho happy path (thành công)',
            'Có test case cho các lỗi validation chính',
            'Có test case cho phân quyền (role sai bị từ chối)',
            'Test chạy pass: php artisan test',
            'Postman collection đã được cập nhật request mới',
        ],
    ];

    private array $moduleChecklist = [
        'auth' => [
            'Password được hash, không trả về trong response',
            'Token Sanctum có expires_at nếu cần',
            'Logout revoke token hiện tại (không revoke tất cả nếu không cần)',
            'Tài khoản locked không được đăng nhập (middleware active)',
        ],
        'hotel' => [
            'hotel_info chỉ có đúng 1 bản ghi singleton — không có create/list/delete',
            'Chỉ admin/staff được sửa thông tin khách sạn (PUT /admin/hotel-info)',
            'Toggle maintenance: khách sạn bảo trì không cho tạo loại phòng mới',
            'Upload ảnh: đường dẫn lưu đúng, không lưu binary vào DB',
            'Amenities được gắn đúng quan hệ many-to-many qua hotel_info_amenity',
        ],
        'room' => [
            'room_types không gắn hotel_id (chỉ có 1 khách sạn duy nhất)',
            'Không hard delete phòng đã có booking, dùng soft delete hoặc inactive',
            'total_rooms không âm, quantity update không vượt quá tổng phòng',
            'price_per_night > 0',
        ],
        'booking' => [
            'check_in >= hôm nay',
            'check_out > check_in (tối thiểu 1 đêm)',
            'Availability check trong DB transaction (tránh race condition)',
            'Chỉ đặt phòng có status active',
            'Booking code duy nhất, format HOMI-YYYYMMDD-XXXXXX',
            'Tổng tiền = price_per_night × nights × quantity',
            'Hủy đơn chỉ được ở trạng thái pending hoặc confirmed',
        ],
        'payment' => [
            'Payment luôn tạo kèm booking (status unpaid ban đầu)',
            'Chỉ refund khi payment đang ở trạng thái paid',
            'Admin hủy đơn đã paid → tự động refund',
            'Không nhận payment amount từ client (tính server-side từ booking)',
        ],
    ];

    public function handle(): int
    {
        $module = $this->option('module');

        $this->newLine();
        $this->line('<fg=cyan;options=bold>╔══════════════════════════════════════════════════╗</>');
        $this->line('<fg=cyan;options=bold>║        HOMI — QA CHECKLIST TRƯỚC MERGE PR       ║</>');
        $this->line('<fg=cyan;options=bold>╚══════════════════════════════════════════════════╝</>');
        $this->newLine();

        foreach ($this->commonChecklist as $section => $items) {
            $this->printSection($section, $items);
        }

        if ($module && isset($this->moduleChecklist[$module])) {
            $this->printSection("Module: {$module}", $this->moduleChecklist[$module], 'yellow');
        } elseif ($module) {
            $this->warn("Không tìm thấy checklist cho module '{$module}'. Các module hợp lệ: " . implode(', ', array_keys($this->moduleChecklist)));
        } else {
            $this->line('<fg=gray>Tip: Thêm --module=booking để xem checklist riêng cho module đó.</>');
        }

        $this->newLine();
        $this->line('<fg=green>Tất cả mục trên đều pass → có thể merge PR.</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function printSection(string $title, array $items, string $color = 'blue'): void
    {
        $this->line("<fg={$color};options=bold>▶ {$title}</>");
        foreach ($items as $item) {
            $this->line("   <fg=white>☐</> {$item}");
        }
        $this->newLine();
    }
}
