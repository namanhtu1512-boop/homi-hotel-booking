<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'ip_address',
    ];

    /**
     * Nhãn tiếng Việt cho từng mã hành động — dùng chung cho nhật ký thao
     * tác theo đơn (BookingTimelineService) và trang "Nhật ký hệ thống"
     * (admin). Action nào chưa có trong bảng này thì actionLabel() trả về
     * nguyên chuỗi gốc, không lỗi.
     */
    public const ACTION_LABELS = [
        // Đơn đặt phòng
        'booking.created_walk_in'         => 'Tạo đơn tại quầy',
        'booking.confirmed'               => 'Xác nhận đơn',
        'booking.cancelled'               => 'Hủy đơn',
        'booking.checked_in'              => 'Check-in',
        'booking.checked_out'             => 'Check-out',
        'booking.completed'               => 'Đánh dấu hoàn thành',
        'booking.payment_updated'         => 'Cập nhật trạng thái thanh toán',
        'booking.service_added'           => 'Thêm dịch vụ phát sinh',
        'booking.surcharge_added'         => 'Thêm phụ phí phát sinh',
        'booking.extended'                => 'Gia hạn lưu trú',
        'room_change_request.approved'    => 'Duyệt yêu cầu đổi phòng',
        'room_change_request.rejected'    => 'Từ chối yêu cầu đổi phòng',
        'extra_bed_request.resolved'      => 'Xử lý yêu cầu giường phụ',
        'early_checkin_request.approved'  => 'Duyệt yêu cầu nhận phòng sớm',
        'early_checkin_request.rejected'  => 'Từ chối yêu cầu nhận phòng sớm',
        'late_checkout_request.approved'  => 'Duyệt yêu cầu trả phòng muộn',
        'late_checkout_request.rejected'  => 'Từ chối yêu cầu trả phòng muộn',

        // Khách hàng / liên hệ
        'contact_message.marked_read'     => 'Đánh dấu đã đọc liên hệ',
        'contact_message.deleted'         => 'Xóa tin nhắn liên hệ',
        'user.status_toggled'             => 'Khóa/mở khóa tài khoản',
        'group_booking_request.booking_created'   => 'Tạo đơn từ yêu cầu đặt đoàn/nhóm',
        'group_booking_request.marked_contacted'  => 'Đánh dấu đã liên hệ đặt đoàn/nhóm',
        'group_booking_request.quote_sent'        => 'Gửi báo giá đặt đoàn/nhóm',
        'group_booking_request.deleted'           => 'Xóa yêu cầu đặt đoàn/nhóm',

        // Cấu hình khách sạn / loại phòng / phòng
        'hotel_info.updated'              => 'Cập nhật thông tin khách sạn',
        'hotel_info.status_toggled'       => 'Đổi trạng thái mở/đóng khách sạn',
        'room_type.created'               => 'Tạo loại phòng',
        'room_type.updated'               => 'Cập nhật loại phòng',
        'room_type.deleted'               => 'Xóa loại phòng',
        'room_type.restored'              => 'Khôi phục loại phòng',
        'room_type.price_updated'         => 'Cập nhật giá loại phòng',
        'room_type.inventory_updated'     => 'Cập nhật số lượng loại phòng',
        'room_type.status_toggled'        => 'Đổi trạng thái loại phòng',
        'room.created'                    => 'Tạo phòng',
        'room.updated'                    => 'Cập nhật phòng',
        'room.deleted'                    => 'Xóa phòng',
        'seasonal_rate.created'           => 'Tạo giá theo mùa',
        'seasonal_rate.updated'           => 'Cập nhật giá theo mùa',
        'seasonal_rate.deleted'           => 'Xóa giá theo mùa',

        // Nội dung / marketing
        'banner.created'                  => 'Tạo banner',
        'banner.updated'                  => 'Cập nhật banner',
        'banner.deleted'                  => 'Xóa banner',
        'news.created'                    => 'Tạo tin tức',
        'news.updated'                    => 'Cập nhật tin tức',
        'news.deleted'                    => 'Xóa tin tức',
        'service.created'                 => 'Tạo dịch vụ',
        'service.updated'                 => 'Cập nhật dịch vụ',
        'service.deleted'                 => 'Xóa dịch vụ',
        'service.restored'                => 'Khôi phục dịch vụ',
        'promotion.created'               => 'Tạo khuyến mãi',
        'promotion.updated'               => 'Cập nhật khuyến mãi',
        'promotion.deleted'               => 'Xóa khuyến mãi',
        'promotion.restored'              => 'Khôi phục khuyến mãi',
        'review.status_toggled'           => 'Đổi trạng thái hiển thị đánh giá',
        'review.deleted'                  => 'Xóa đánh giá',
    ];

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }
}
