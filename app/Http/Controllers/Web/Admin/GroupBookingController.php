<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\BookingService;
use App\Services\ChatService;
use App\Services\GroupBookingRequestService;
use App\Services\HotelInfoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupBookingController extends Controller
{
    public function __construct(
        private readonly GroupBookingRequestService $groupBookingRequestService,
        private readonly BookingService $bookingService,
        private readonly AuditLogService $auditLog,
        private readonly ChatService $chatService,
        private readonly HotelInfoService $hotelInfoService,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.group-bookings.index', [
            'requests' => $this->groupBookingRequestService->adminList($request->only('status')),
            'filters'  => $request->only('status'),
        ]);
    }

    public function show(int $id): View
    {
        $groupRequest = GroupBookingRequest::findOrFail($id);
        $allRoomTypes = RoomType::where('status', 'active')->orderBy('name')->get();

        return view('admin.group-bookings.show', [
            'groupRequest' => $groupRequest,
            'roomTypes'    => $allRoomTypes,
            'allRoomTypes' => $allRoomTypes,
            'prefillItems' => $this->groupBookingRequestService->defaultPrefillItems($groupRequest),
            'extraBedSurchargePerNight' => $this->hotelInfoService->current()->extra_bed_surcharge_per_night,
        ]);
    }

    public function createBooking(int $id, Request $request): RedirectResponse
    {
        $groupRequest = GroupBookingRequest::findOrFail($id);

        // Yêu cầu đã được chuyển thành đơn trước đó — chặn tạo đơn trùng lần 2
        // (form "Tạo đơn đặt phòng" vẫn hiển thị nếu admin quay lại trang cũ).
        if ($groupRequest->status === 'converted') {
            return redirect()->route('admin.group-bookings.show', $id)
                ->with('error', 'Yêu cầu này đã được chuyển thành đơn đặt phòng trước đó, không thể tạo thêm.');
        }

        $data = $request->validate([
            'check_in'        => ['required', 'date', 'after_or_equal:' . now('Asia/Ho_Chi_Minh')->toDateString()],
            'check_out'       => ['required', 'date', 'after:check_in'],
            'items'           => ['required', 'array', 'min:1'],
            'items.*.room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'items.*.adults'       => ['required', 'integer', 'min:1'],
            'items.*.children'     => ['nullable', 'integer', 'min:0'],
            'items.*.extra_bed'    => ['nullable', 'boolean'],
            'customer_name'   => ['required', 'string', 'max:100'],
            'customer_phone'  => ['required', 'string', 'max:20'],
            'customer_email'  => ['nullable', 'email', 'max:150'],
            'note'            => ['nullable', 'string', 'max:2000'],
        ]);

        // Gắn đơn vào tài khoản khách (nếu yêu cầu đoàn được gửi khi đã đăng
        // nhập) — thiếu dòng này khiến đơn bị tạo với user_id = null, không
        // hiện trong "đơn của tôi" dù khách đã tự gửi yêu cầu.
        $data['user_id'] = $groupRequest->user_id;

        $booking = $this->bookingService->createByAdmin($data);

        // Đánh dấu yêu cầu đoàn đã được chuyển thành đơn — trạng thái cuối,
        // chặn tạo đơn trùng nếu admin submit lại form.
        $this->groupBookingRequestService->markConverted($groupRequest);

        $this->auditLog->log('group_booking_request.booking_created', $booking, "Tạo đơn {$booking->booking_code} từ yêu cầu đoàn #{$groupRequest->id}.");

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('success', "Đã tạo đơn đặt phòng {$booking->booking_code} từ yêu cầu đoàn.");
    }

    public function sendQuote(int $id, Request $request): RedirectResponse
    {
        $groupRequest = GroupBookingRequest::with('user')->findOrFail($id);

        if (! $groupRequest->user) {
            return redirect()->route('admin.group-bookings.show', $id)
                ->with('error', 'Yêu cầu này không có tài khoản liên kết, không thể gửi qua chat.');
        }

        $data = $request->validate([
            'note'                          => ['nullable', 'string', 'max:2000'],
            'quote_items'                   => ['required', 'array', 'min:1'],
            'quote_items.*.room_type_id'    => ['required', 'integer', 'exists:room_types,id'],
            'quote_items.*.quantity'        => ['required', 'integer', 'min:1'],
            'quote_items.*.price_per_night' => ['required', 'numeric', 'min:0'],
            'extra_beds'                    => ['nullable', 'integer', 'min:0'],
            'extra_bed_price_per_night'     => ['nullable', 'numeric', 'min:0'],
        ]);

        $roomTypes = RoomType::whereIn('id', array_column($data['quote_items'], 'room_type_id'))->get()->keyBy('id');

        // max(1, ...) — nếu khách chọn check_in = check_out (0 đêm theo diffInDays)
        // vẫn tính tối thiểu 1 đêm, tránh báo giá 0đ vô nghĩa.
        $nights = ($groupRequest->check_in && $groupRequest->check_out)
            ? max(1, $groupRequest->check_in->diffInDays($groupRequest->check_out))
            : null;

        $lines = ["**Báo giá đặt phòng đoàn/nhóm** (Yêu cầu #{$groupRequest->id})"];
        if ($nights) $lines[] = "Thời gian: {$groupRequest->check_in->format('d/m/Y')} → {$groupRequest->check_out->format('d/m/Y')} ({$nights} đêm)";

        $total = 0;
        foreach ($data['quote_items'] as $item) {
            $name     = $roomTypes[$item['room_type_id']]?->name ?? '?';
            $subtotal = $item['quantity'] * $item['price_per_night'] * ($nights ?? 1);
            $total   += $subtotal;
            $lines[]  = "- {$name}: {$item['quantity']} phòng × " . number_format($item['price_per_night'], 0, ',', '.') . 'đ/đêm'
                . ($nights ? ' = ' . number_format($subtotal, 0, ',', '.') . 'đ' : '');
        }

        // Phụ thu giường phụ (trẻ em 6-11 tuổi) — nhân viên tự nhập số giường
        // và giá, KHÔNG tự động suy ra từ num_children của yêu cầu đoàn, vì
        // chỉ loại phòng có RoomType::supportsExtraBed() = true mới hỗ trợ
        // giường phụ thật — form này không biết nhân viên đã chọn đúng loại
        // phòng phù hợp hay chưa, để nhân viên tự quyết (blade cảnh báo qua
        // updateExtraBedWarning() đọc động data-supports-extra-bed).
        $extraBeds = (int) ($data['extra_beds'] ?? 0);
        if ($extraBeds > 0) {
            $extraBedPrice    = (float) ($data['extra_bed_price_per_night'] ?? 0);
            $extraBedSubtotal = $extraBeds * $extraBedPrice * ($nights ?? 1);
            $total           += $extraBedSubtotal;
            $lines[]          = "- Giường phụ trẻ em (6-11 tuổi): {$extraBeds} giường × " . number_format($extraBedPrice, 0, ',', '.') . 'đ/đêm'
                . ($nights ? ' = ' . number_format($extraBedSubtotal, 0, ',', '.') . 'đ' : '');
        }

        if ($nights) $lines[] = "**Tổng dự kiến: " . number_format($total, 0, ',', '.') . 'đ** (chưa bao gồm dịch vụ phát sinh)';
        if ($data['note'] ?? null) $lines[] = "\n{$data['note']}";
        $lines[] = "\nXem phòng và đặt ngay: " . route('rooms.index');

        $this->chatService->send($groupRequest->user_id, $request->user(), implode("\n", $lines));

        $this->groupBookingRequestService->markContacted($groupRequest);

        $this->auditLog->log('group_booking_request.quote_sent', $groupRequest->fresh(), "Gửi báo giá chat cho yêu cầu đoàn #{$groupRequest->id}.");

        return redirect()->route('admin.group-bookings.show', $id)
            ->with('success', 'Đã gửi báo giá qua chat đến ' . $groupRequest->user->name . '.');
    }

    public function markContacted(int $id): RedirectResponse
    {
        $groupBookingRequest = GroupBookingRequest::findOrFail($id);

        $this->groupBookingRequestService->markContacted($groupBookingRequest);

        $this->auditLog->log('group_booking_request.marked_contacted', $groupBookingRequest->fresh(), "Đánh dấu đã liên hệ yêu cầu đặt đoàn #{$groupBookingRequest->id}.");

        return redirect()->route('admin.group-bookings.index')->with('success', 'Đã đánh dấu đã liên hệ.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $groupBookingRequest = GroupBookingRequest::findOrFail($id);

        $this->groupBookingRequestService->delete($groupBookingRequest);

        $this->auditLog->log('group_booking_request.deleted', null, "Xóa yêu cầu đặt đoàn #{$id}.");

        return redirect()->route('admin.group-bookings.index')->with('success', 'Đã xóa yêu cầu.');
    }
}
