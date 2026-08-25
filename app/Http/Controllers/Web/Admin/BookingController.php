<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\SurchargeCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AddBookingServiceRequest;
use App\Http\Requests\Booking\AddSurchargeRequest;
use App\Http\Requests\Booking\CreateWalkInBookingRequest;
use App\Http\Requests\Booking\ExtendStayRequest;
use App\Http\Requests\Booking\UpdatePaymentStatusRequest;
use App\Models\BookingItemRoom;
use App\Models\RoomType;
use App\Services\AuditLogService;
use App\Services\BookingService;
use App\Services\BookingTimelineService;
use App\Services\RoomService;
use App\Services\ServiceService;
use App\Services\SurchargeItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly AuditLogService $auditLog,
        private readonly RoomService $roomService,
        private readonly ServiceService $serviceService,
        private readonly SurchargeItemService $surchargeItemService,
        private readonly BookingTimelineService $timelineService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'status',
            'payment_status',
            'booking_code',
            'customer_name',
            'created_from',
            'created_to',
            'check_in_from',
            'check_in_to',
            'room_type_id',
        ]);

        $bookings = $this->bookingService->adminList($filters, 20)->appends($filters);

        return view('admin.bookings.index', [
            'bookings'  => $bookings,
            'filters'   => $filters,
            'roomTypes' => RoomType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(int $id): View
    {
        $booking = $this->bookingService->findForAdmin($id);

        $checkedInRooms = $booking->bookingItemRooms()
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->with(['room', 'bookingItem.roomType'])
            ->get();

        return view('admin.bookings.show', [
            'booking'         => $booking,
            'activeServices'  => $this->serviceService->activePublic(),
            'damageItems'     => $this->surchargeItemService->activePublic(SurchargeCategory::Damage),
            'violationItems'  => $this->surchargeItemService->activePublic(SurchargeCategory::Violation),
            'cleaningItems'   => $this->surchargeItemService->activePublic(SurchargeCategory::Cleaning),
            'timeline'        => $this->timelineService->buildTimeline($booking),
            'checkedInRooms'  => $checkedInRooms,
        ]);
    }

    public function create(): View
    {
        return view('admin.bookings.create', [
            'roomTypes' => RoomType::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(CreateWalkInBookingRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->createByAdmin($request->validated());

        $this->auditLog->log('booking.created_walk_in', $booking, "Tạo đơn tại quầy \"{$booking->booking_code}\".");

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('success', "Đã tạo đơn đặt phòng tại quầy {$booking->booking_code}.");
    }

    public function invoice(int $id, Request $request): View
    {
        $booking = $this->bookingService->findForAdmin($id);
        $roomId = $request->query('room') ? (int) $request->query('room') : null;
        $room = $roomId
            ? $booking->bookingItemRooms()->with(['room', 'bookingItem.roomType', 'settlement'])->find($roomId)
            : null;
        $roomPreview = ($room && ! $room->settlement) ? $this->bookingService->previewRoomSettlement($booking, $room) : null;

        return view('bookings.invoice', [
            'booking'     => $booking,
            'room'        => $room,
            'roomPreview' => $roomPreview,
            'backRoute'   => route('admin.bookings.show', $id),
        ]);
    }

    public function confirm(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->confirm($booking);

        $this->auditLog->log('booking.confirmed', $booking, "Xác nhận đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', "Đã xác nhận đơn {$booking->booking_code}.");
    }

    public function cancel(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $result = $this->bookingService->cancelByAdmin($booking);

        $this->auditLog->log('booking.cancelled', $booking, "Hủy đơn \"{$booking->booking_code}\".");

        $refundAmount = number_format($result['refund_amount'], 0, ',', '.') . 'đ';

        if (! $result['refund_ok']) {
            return redirect()
                ->route('admin.bookings.show', $id)
                ->with('error', "Đã hủy đơn {$booking->booking_code}, nhưng hoàn tiền tự động qua VNPay không thành công — cần xử lý hoàn tiền thủ công {$refundAmount}.");
        }

        $message = $result['refund_amount'] > 0
            ? "Đã hủy đơn {$booking->booking_code} — đã hoàn {$refundAmount} cho khách."
            : "Đã hủy đơn {$booking->booking_code}.";

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', $message);
    }

    public function showCheckIn(int $id): View
    {
        $booking = $this->bookingService->findForAdmin($id);

        $availableRooms = $booking->bookingItems->mapWithKeys(
            fn ($item) => [$item->id => $this->roomService->availableForRoomType($item->room_type_id)]
        );

        return view('bookings.check-in', [
            'booking'        => $booking,
            'availableRooms' => $availableRooms,
            'formAction'     => route('admin.bookings.check-in', $id),
            'backRoute'      => route('admin.bookings.show', $id),
            'layout'         => 'layouts.admin',
        ]);
    }

    public function checkIn(int $id, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);

        $roomAssignments = collect($request->input('rooms', []))
            ->map(fn ($roomIds) => array_map('intval', (array) $roomIds))
            ->all();

        $result = $this->bookingService->checkIn($booking, $roomAssignments);

        $this->auditLog->log('booking.checked_in', $booking, "Check-in đơn \"{$booking->booking_code}\".");

        $message = "Đã check-in đơn {$booking->booking_code}.";
        if ($result['early_checkin_fee']) {
            $message .= ' Đã tự động cộng phụ phí nhận phòng sớm ' . number_format($result['early_checkin_fee'], 0, ',', '.') . 'đ.';
        }

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', $message);
    }

    public function showCheckOut(int $id): View
    {
        $booking = $this->bookingService->findForAdmin($id);

        $pendingRooms = $booking->bookingItemRooms()
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->with(['room', 'bookingItem.roomType'])
            ->get()
            ->map(fn (BookingItemRoom $room) => [
                'room'    => $room,
                'preview' => $this->bookingService->previewRoomSettlement($booking, $room),
            ]);

        return view('bookings.check-out', [
            'booking'      => $booking,
            'pendingRooms' => $pendingRooms,
            'formAction'   => route('admin.bookings.check-out', $id),
            'backRoute'    => route('admin.bookings.show', $id),
            'layout'       => 'layouts.admin',
        ]);
    }

    public function checkOut(int $id, Request $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);

        $roomIds = array_map('intval', (array) $request->input('rooms', []));

        if (empty($roomIds)) {
            return redirect()->back()->with('error', 'Vui lòng chọn ít nhất 1 phòng để trả phòng.');
        }

        $settlementInput = [
            'method' => $request->input('method'),
            'note'   => $request->input('note'),
        ];

        $totalCollected = 0.0;
        $lateFee = null;

        foreach ($roomIds as $roomId) {
            $bookingItemRoom = BookingItemRoom::findOrFail($roomId);
            $result = $this->bookingService->checkOutRoom($booking, $bookingItemRoom, $settlementInput);

            $totalCollected += (float) $result['settlement']->amount_collected;
            $lateFee = $lateFee ?? $result['late_checkout_fee'];
            $booking = $result['booking'];
        }

        $this->auditLog->log('booking.checked_out', $booking, 'Trả phòng cho đơn "' . $booking->booking_code . '" (' . count($roomIds) . ' phòng).');

        $message = 'Đã trả phòng (' . count($roomIds) . ") phòng cho đơn {$booking->booking_code} — thu " . number_format($totalCollected, 0, ',', '.') . 'đ.';
        if ($lateFee) {
            $message .= ' Đã tự động cộng phụ phí trả phòng muộn ' . number_format($lateFee, 0, ',', '.') . 'đ.';
        }

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', $message);
    }

    public function complete(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->complete($booking);

        $this->auditLog->log('booking.completed', $booking, "Đánh dấu hoàn thành đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', "Đã đánh dấu hoàn thành đơn {$booking->booking_code}.");
    }

    public function updatePayment(int $id, UpdatePaymentStatusRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->updatePaymentStatus($booking, $request->validated('status'));

        $this->auditLog->log('booking.payment_updated', $booking, "Cập nhật thanh toán đơn \"{$booking->booking_code}\" thành \"{$request->validated('status')}\".");

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', "Đã cập nhật trạng thái thanh toán đơn {$booking->booking_code}.");
    }

    public function addService(int $id, AddBookingServiceRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->addServiceItem(
            $booking,
            (int) $request->validated('service_id'),
            (int) $request->validated('quantity'),
            $request->validated('amount') !== null ? (float) $request->validated('amount') : null,
            $request->validated('note'),
            $request->validated('booking_item_room_id') ? (int) $request->validated('booking_item_room_id') : null,
        );

        $this->auditLog->log('booking.service_added', $booking, "Thêm dịch vụ phát sinh cho đơn \"{$booking->booking_code}\".");

        return redirect()
            ->back()
            ->with('success', "Đã thêm dịch vụ phát sinh cho đơn {$booking->booking_code}.");
    }

    public function addSurcharge(int $id, AddSurchargeRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->addSurcharge(
            $booking,
            (float) $request->validated('amount'),
            $request->validated('note'),
            $request->validated('surcharge_item_id') ? (int) $request->validated('surcharge_item_id') : null,
            (int) ($request->validated('quantity') ?: 1),
            $request->validated('booking_item_room_id') ? (int) $request->validated('booking_item_room_id') : null,
        );

        $this->auditLog->log('booking.surcharge_added', $booking, "Thêm phụ phí phát sinh cho đơn \"{$booking->booking_code}\".");

        return redirect()
            ->back()
            ->with('success', "Đã thêm phụ phí phát sinh cho đơn {$booking->booking_code}.");
    }

    public function previewExtendStay(int $id, Request $request): JsonResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $switchRoomTypeId = $request->query('switch_room_type_id') ? (int) $request->query('switch_room_type_id') : null;
        $switchRoomId     = $request->query('switch_room_id') ? (int) $request->query('switch_room_id') : null;

        try {
            $preview = $this->bookingService->previewExtendStay($booking, (string) $request->query('new_check_out', ''), $switchRoomTypeId, $switchRoomId);

            return response()->json(['ok' => true] + $preview);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }
    }

    public function extendStay(int $id, ExtendStayRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $result = $this->bookingService->extendStay(
            $booking,
            $request->validated('new_check_out'),
            $request->validated('switch_room_type_id') ? (int) $request->validated('switch_room_type_id') : null,
            $request->validated('switch_room_id') ? (int) $request->validated('switch_room_id') : null,
        );

        $switchNote = $result['switched'] ? ', đổi sang loại phòng khác' : '';
        $this->auditLog->log('booking.extended', $booking, "Gia hạn đơn \"{$booking->booking_code}\" thêm {$result['nights_added']} đêm{$switchNote}.");

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', "Đã gia hạn đơn {$booking->booking_code} thêm {$result['nights_added']} đêm — phát sinh " . number_format($result['extra_amount'], 0, ',', '.') . 'đ, thu khi trả phòng.');
    }
}
