<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\BookingService;
use App\Services\ChatService;
use App\Services\GroupBookingRequestService;
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
    ) {}

    public function index(Request $request): View
    {
        return view('staff.group-bookings.index', [
            'requests' => $this->groupBookingRequestService->adminList($request->only('status')),
            'filters'  => $request->only('status'),
        ]);
    }

    public function show(int $id): View
    {
        $groupRequest = GroupBookingRequest::findOrFail($id);
        $allRoomTypes = RoomType::where('status', 'active')->orderBy('name')->get();

        return view('staff.group-bookings.show', [
            'groupRequest' => $groupRequest,
            'roomTypes'    => $allRoomTypes,
            'allRoomTypes' => $allRoomTypes,
            'chatUrl'      => $groupRequest->user_id ? route('staff.chat.show', $groupRequest->user_id) : null,
            'prefillItems' => $groupRequest->room_type_ids
                ? array_map(fn($rid) => ['room_type_id' => $rid, 'quantity' => 1, 'adults' => 2, 'children' => 0], $groupRequest->room_type_ids)
                : [['room_type_id' => '', 'quantity' => 1, 'adults' => 2, 'children' => 0]],
        ]);
    }

    public function createBooking(int $id, Request $request): RedirectResponse
    {
        $groupRequest = GroupBookingRequest::findOrFail($id);

        // Yêu cầu đã được chuyển thành đơn trước đó — chặn tạo đơn trùng lần 2
        // (form "Tạo đơn đặt phòng" vẫn hiển thị nếu staff quay lại trang cũ).
        if ($groupRequest->status === 'converted') {
            return redirect()->route('staff.group-bookings.show', $id)
                ->with('error', 'Yêu cầu này đã được chuyển thành đơn đặt phòng trước đó, không thể tạo thêm.');
        }

        $data = $request->validate([
            'check_in'             => ['required', 'date', 'after_or_equal:today'],
            'check_out'            => ['required', 'date', 'after:check_in'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'items.*.adults'       => ['required', 'integer', 'min:1'],
            'items.*.children'     => ['nullable', 'integer', 'min:0'],
            'customer_name'        => ['required', 'string', 'max:100'],
            'customer_phone'       => ['required', 'string', 'max:20'],
            'customer_email'       => ['nullable', 'email', 'max:150'],
            'note'                 => ['nullable', 'string', 'max:2000'],
        ]);

        $booking = $this->bookingService->createByAdmin($data);

        // Đánh dấu yêu cầu đoàn đã được chuyển thành đơn — trạng thái cuối,
        // chặn tạo đơn trùng nếu staff submit lại form.
        $this->groupBookingRequestService->markConverted($groupRequest);

        $this->auditLog->log('group_booking_request.booking_created', $booking, "Tạo đơn {$booking->booking_code} từ yêu cầu đoàn #{$groupRequest->id}.");

        return redirect()
            ->route('staff.bookings.show', $booking->id)
            ->with('success', "Đã tạo đơn đặt phòng {$booking->booking_code} từ yêu cầu đoàn.");
    }

    public function markContacted(int $id): RedirectResponse
    {
        $groupRequest = GroupBookingRequest::findOrFail($id);

        $this->groupBookingRequestService->markContacted($groupRequest);

        $this->auditLog->log('group_booking_request.marked_contacted', $groupRequest->fresh(), "Đánh dấu đã liên hệ yêu cầu đặt đoàn #{$groupRequest->id}.");

        return redirect()->route('staff.group-bookings.index')->with('success', 'Đã đánh dấu đã liên hệ.');
    }

    public function sendQuote(int $id, Request $request): RedirectResponse
    {
        $groupRequest = GroupBookingRequest::with('user')->findOrFail($id);

        if (! $groupRequest->user) {
            return redirect()->route('staff.group-bookings.show', $id)
                ->with('error', 'Yêu cầu này không có tài khoản liên kết, không thể gửi qua chat.');
        }

        $data = $request->validate([
            'note'                          => ['nullable', 'string', 'max:2000'],
            'quote_items'                   => ['required', 'array', 'min:1'],
            'quote_items.*.room_type_id'    => ['required', 'integer', 'exists:room_types,id'],
            'quote_items.*.quantity'        => ['required', 'integer', 'min:1'],
            'quote_items.*.price_per_night' => ['required', 'numeric', 'min:0'],
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
        if ($nights) $lines[] = "**Tổng dự kiến: " . number_format($total, 0, ',', '.') . 'đ** (chưa bao gồm dịch vụ phát sinh)';
        if ($data['note'] ?? null) $lines[] = "\n{$data['note']}";
        $lines[] = "\nXem phòng và đặt ngay: " . route('rooms.index');

        $this->chatService->send($groupRequest->user_id, $request->user(), implode("\n", $lines));

        $this->groupBookingRequestService->markContacted($groupRequest);

        $this->auditLog->log('group_booking_request.quote_sent', $groupRequest->fresh(), "Gửi báo giá chat cho yêu cầu đoàn #{$groupRequest->id}.");

        return redirect()->route('staff.group-bookings.show', $id)
            ->with('success', 'Đã gửi báo giá qua chat đến ' . $groupRequest->user->name . '.');
    }
}
