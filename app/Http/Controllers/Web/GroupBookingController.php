<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewGroupBookingRequest;
use App\Services\GroupBookingRequestService;
use App\Services\HotelInfoService;
use App\Services\RoomTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupBookingController extends Controller
{
    public function __construct(
        private readonly GroupBookingRequestService $groupBookingRequestService,
        private readonly HotelInfoService $hotelInfoService,
        private readonly RoomTypeService $roomTypeService,
    ) {}

    public function show(): View
    {
        return view('client.group-booking', [
            'hotel'     => $this->hotelInfoService->current(),
            'roomTypes' => $this->roomTypeService->list(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name'    => ['nullable', 'string', 'max:150'],
            'contact_name'    => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'max:150'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'group_size'      => ['required', 'integer', 'min:1'],
            'room_count'      => ['required', 'integer', 'min:5'],
            'check_in'        => ['nullable', 'date'],
            'check_out'       => ['nullable', 'date', 'after_or_equal:check_in'],
            'room_type_ids'   => ['nullable', 'array'],
            'room_type_ids.*' => ['integer', 'exists:room_types,id'],
            'message'         => ['nullable', 'string', 'max:2000'],
        ], [], [
            'company_name'  => 'tên công ty',
            'contact_name'  => 'họ tên liên hệ',
            'email'         => 'email',
            'phone'         => 'số điện thoại',
            'group_size'    => 'số lượng khách',
            'room_count'    => 'số phòng',
            'check_in'      => 'ngày nhận phòng dự kiến',
            'check_out'     => 'ngày trả phòng dự kiến',
            'room_type_ids' => 'loại phòng quan tâm',
            'message'       => 'ghi chú',
        ]);

        $data['user_id'] = $request->user()?->id;

        $groupRequest = $this->groupBookingRequestService->create($data);

        User::whereIn('role', ['admin', 'staff'])->each(
            fn (User $u) => $u->notify(new NewGroupBookingRequest($groupRequest))
        );

        return redirect()
            ->route('group-bookings.show')
            ->with('success', 'Đã gửi yêu cầu đặt đoàn/nhóm! Homi sẽ liên hệ báo giá sớm nhất.');
    }
}
