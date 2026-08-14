<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Mail\GroupBookingConfirmedMail;
use App\Mail\GroupBookingQuoteMail;
use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\BookingService;
use App\Services\ChatService;
use App\Services\GroupBookingRequestService;
use App\Services\HotelInfoService;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class GroupBookingController extends Controller
{
    public function __construct(
        private readonly GroupBookingRequestService $groupBookingRequestService,
        private readonly BookingService $bookingService,
        private readonly AuditLogService $auditLog,
        private readonly ChatService $chatService,
        private readonly HotelInfoService $hotelInfoService,
        private readonly PromotionService $promotionService,
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
            'groupPromotions' => $this->promotionService->activeGroupPromotions(),
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
            'customer_email'  => ['required', 'email', 'max:150'],
            'note'            => ['nullable', 'string', 'max:2000'],
            'promo_codes_text' => ['nullable', 'string', 'max:250'],
        ]);

        // Gắn đơn vào tài khoản khách (nếu yêu cầu đoàn được gửi khi đã đăng
        // nhập) — thiếu dòng này khiến đơn bị tạo với user_id = null, không
        // hiện trong "đơn của tôi" dù khách đã tự gửi yêu cầu.
        $data['user_id'] = $groupRequest->user_id;

        // 1 ô nhập, nhiều mã ngăn cách bằng dấu phẩy — cùng cách
        // customer/booking/create.blade.php làm cho khách tự đặt.
        $data['promo_codes'] = collect(explode(',', $data['promo_codes_text'] ?? ''))
            ->map(fn ($code) => trim($code))
            ->filter()
            ->values()
            ->all();

        $booking = $this->bookingService->createByAdmin($data);

        // Đánh dấu yêu cầu đoàn đã được chuyển thành đơn — trạng thái cuối,
        // chặn tạo đơn trùng nếu admin submit lại form.
        $this->groupBookingRequestService->markConverted($groupRequest);

        // Gửi email xác nhận tới đúng địa chỉ khách nhập trên form (bắt buộc
        // nhập) — không phụ thuộc đơn có gắn tài khoản hay không, khác với
        // thông báo trong app (BookingService::createByAdmin() chỉ bắn được
        // nếu có $booking->user, nên nhiều đơn đoàn/nhóm không có ai nhận).
        Mail::to($booking->customer_email)->send(new GroupBookingConfirmedMail($booking));

        $this->auditLog->log('group_booking_request.booking_created', $booking, "Tạo đơn {$booking->booking_code} từ yêu cầu đoàn #{$groupRequest->id}.");

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('success', "Đã tạo đơn đặt phòng {$booking->booking_code} từ yêu cầu đoàn.");
    }

    public function sendQuote(int $id, Request $request): RedirectResponse
    {
        $groupRequest = GroupBookingRequest::with('user')->findOrFail($id);

        $data = $request->validate([
            'note'                          => ['nullable', 'string', 'max:2000'],
            'quote_items'                   => ['required', 'array', 'min:1'],
            'quote_items.*.room_type_id'    => ['required', 'integer', 'exists:room_types,id'],
            'quote_items.*.quantity'        => ['required', 'integer', 'min:1'],
            'quote_items.*.price_per_night' => ['required', 'numeric', 'min:0'],
            'extra_beds'                    => ['nullable', 'integer', 'min:0'],
            'extra_bed_price_per_night'     => ['nullable', 'numeric', 'min:0'],
        ]);

        $quote = $this->groupBookingRequestService->buildQuote($groupRequest, $data);

        if ($groupRequest->user) {
            $this->chatService->send($groupRequest->user_id, $request->user(), implode("\n", $quote['lines']));
        }

        Mail::to($groupRequest->email)->send(new GroupBookingQuoteMail($groupRequest, $quote));

        $this->groupBookingRequestService->markContacted($groupRequest);

        $channel = $groupRequest->user ? 'email+chat' : 'email';
        $this->auditLog->log('group_booking_request.quote_sent', $groupRequest->fresh(), "Gửi báo giá ({$channel}) cho yêu cầu đoàn #{$groupRequest->id}.");

        $successMessage = 'Đã gửi báo giá qua email' . ($groupRequest->user ? ' và chat' : '') . ' đến ' . $groupRequest->email . '.';

        return redirect()->route('admin.group-bookings.show', $id)->with('success', $successMessage);
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
