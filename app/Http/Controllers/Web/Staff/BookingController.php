<?php

namespace App\Http\Controllers\Web\Staff;

use App\Enums\SurchargeCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AddBookingServiceRequest;
use App\Http\Requests\Booking\AddSurchargeRequest;
use App\Http\Requests\Booking\CreateWalkInBookingRequest;
use App\Http\Requests\Booking\ExtendStayRequest;
use App\Http\Requests\Booking\UpdatePaymentStatusRequest;
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

        return view('staff.bookings.index', [
            'bookings'  => $bookings,
            'filters'   => $filters,
            'roomTypes' => RoomType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(int $id): View
    {
        $booking = $this->bookingService->findForAdmin($id);

        return view('staff.bookings.show', [
            'booking'         => $booking,
            'activeServices'  => $this->serviceService->activePublic(),
            'damageItems'     => $this->surchargeItemService->activePublic(SurchargeCategory::Damage),
            'violationItems'  => $this->surchargeItemService->activePublic(SurchargeCategory::Violation),
            'cleaningItems'   => $this->surchargeItemService->activePublic(SurchargeCategory::Cleaning),
            'timeline'        => $this->timelineService->buildTimeline($booking),
        ]);
    }

    public function create(): View
    {
        return view('staff.bookings.create', [
            'roomTypes' => RoomType::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(CreateWalkInBookingRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->createByAdmin($request->validated());

        $this->auditLog->log('booking.created_walk_in', $booking, "Tạo đơn tại quầy \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $booking->id)
            ->with('success', "Đã tạo đơn đặt phòng tại quầy {$booking->booking_code}.");
    }

    public function invoice(int $id): View
    {
        return view('bookings.invoice', [
            'booking'   => $this->bookingService->findForAdmin($id),
            'backRoute' => route('staff.bookings.show', $id),
        ]);
    }

    public function confirm(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->confirm($booking);

        $this->auditLog->log('booking.confirmed', $booking, "Xác nhận đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã xác nhận đơn {$booking->booking_code}.");
    }

    public function cancel(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $result = $this->bookingService->cancelByAdmin($booking);

        $this->auditLog->log('booking.cancelled', $booking, "Hủy đơn \"{$booking->booking_code}\".");

        if (! $result['refund_ok']) {
            return redirect()
                ->route('staff.bookings.show', $id)
                ->with('error', "Đã hủy đơn {$booking->booking_code}, nhưng hoàn tiền tự động qua VNPay không thành công — cần xử lý hoàn tiền thủ công.");
        }

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã hủy đơn {$booking->booking_code}.");
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
            'formAction'     => route('staff.bookings.check-in', $id),
            'backRoute'      => route('staff.bookings.show', $id),
            'layout'         => 'layouts.staff',
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
            ->route('staff.bookings.show', $id)
            ->with('success', $message);
    }

    public function showCheckOut(int $id): View
    {
        $booking = $this->bookingService->findForAdmin($id);

        return view('bookings.check-out', [
            'booking'    => $booking,
            'formAction' => route('staff.bookings.check-out', $id),
            'backRoute'  => route('staff.bookings.show', $id),
            'layout'     => 'layouts.staff',
        ]);
    }

    public function checkOut(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->checkOut($booking);

        $this->auditLog->log('booking.checked_out', $booking, "Check-out đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã check-out đơn {$booking->booking_code}.");
    }

    public function complete(int $id): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->complete($booking);

        $this->auditLog->log('booking.completed', $booking, "Đánh dấu hoàn thành đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã đánh dấu hoàn thành đơn {$booking->booking_code}.");
    }

    public function updatePayment(int $id, UpdatePaymentStatusRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->findForAdmin($id);
        $this->bookingService->updatePaymentStatus($booking, $request->validated('status'));

        $this->auditLog->log('booking.payment_updated', $booking, "Cập nhật thanh toán đơn \"{$booking->booking_code}\" thành \"{$request->validated('status')}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
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
        );

        $this->auditLog->log('booking.service_added', $booking, "Thêm dịch vụ phát sinh cho đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
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
        );

        $this->auditLog->log('booking.surcharge_added', $booking, "Thêm phụ phí phát sinh cho đơn \"{$booking->booking_code}\".");

        return redirect()
            ->route('staff.bookings.show', $id)
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
            ->route('staff.bookings.show', $id)
            ->with('success', "Đã gia hạn đơn {$booking->booking_code} thêm {$result['nights_added']} đêm — phát sinh " . number_format($result['extra_amount'], 0, ',', '.') . 'đ, thu khi trả phòng.');
    }
}
