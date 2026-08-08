<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewGroupBookingRequest;
use App\Services\GroupBookingRequestService;
use App\Services\GroupBookingSuggestionService;
use App\Services\HotelInfoService;
use App\Services\PromotionService;
use App\Services\RoomTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupBookingController extends Controller
{
    public function __construct(
        private readonly GroupBookingRequestService $groupBookingRequestService,
        private readonly GroupBookingSuggestionService $groupBookingSuggestionService,
        private readonly HotelInfoService $hotelInfoService,
        private readonly RoomTypeService $roomTypeService,
        private readonly PromotionService $promotionService,
    ) {}

    public function show(): View
    {
        return view('client.group-booking', [
            'hotel'           => $this->hotelInfoService->current(),
            'roomTypes'       => $this->roomTypeService->list(),
            'groupPromotions' => $this->promotionService->activeGroupPromotions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name'         => ['nullable', 'string', 'max:150'],
            'contact_name'         => ['required', 'string', 'max:100'],
            'email'                => ['required', 'email', 'max:150'],
            'phone'                => ['nullable', 'string', 'max:20'],
            'group_size'           => ['required', 'integer', 'min:1'],
            'num_children'         => ['nullable', 'integer', 'min:0'],
            'num_infants'          => ['nullable', 'integer', 'min:0'],
            'room_count'           => ['required', 'integer', 'min:5'],
            'check_in'             => ['nullable', 'date'],
            'check_out'            => ['nullable', 'date', 'after_or_equal:check_in'],
            'room_type_ids'        => ['nullable', 'array'],
            'room_type_ids.*'      => ['integer', 'exists:room_types,id'],
            'message'              => ['nullable', 'string', 'max:2000'],
            'selected_suggestion_json' => ['nullable', 'string'],
        ], [], [
            'company_name'  => 'tên công ty',
            'contact_name'  => 'họ tên liên hệ',
            'email'         => 'email',
            'phone'         => 'số điện thoại',
            'group_size'    => 'số lượng khách',
            'num_children'  => 'số trẻ em (6-11 tuổi)',
            'num_infants'   => 'số trẻ sơ sinh (0-5 tuổi)',
            'room_count'    => 'số phòng',
            'check_in'      => 'ngày nhận phòng dự kiến',
            'check_out'     => 'ngày trả phòng dự kiến',
            'room_type_ids' => 'loại phòng quan tâm',
            'message'       => 'ghi chú',
        ]);

        // selected_suggestion đến từ hidden input dạng chuỗi JSON (form-encoded
        // không giữ được cấu trúc mảng lồng nhau gọn gàng) — giải mã ở đây rồi
        // gán vào field JSON thật của model, bỏ qua nếu khách không dùng gợi ý.
        $decodedSuggestion = ! empty($data['selected_suggestion_json'])
            ? json_decode($data['selected_suggestion_json'], true)
            : null;
        unset($data['selected_suggestion_json']);
        $data['selected_suggestion'] = is_array($decodedSuggestion) ? $decodedSuggestion : null;

        $data['user_id'] = $request->user()?->id;

        $groupRequest = $this->groupBookingRequestService->create($data);

        User::whereIn('role', ['admin', 'staff'])->each(
            fn (User $u) => $u->notify(new NewGroupBookingRequest($groupRequest))
        );

        return redirect()
            ->route('group-bookings.show')
            ->with('success', 'Đã gửi yêu cầu đặt đoàn/nhóm! Homi sẽ liên hệ báo giá sớm nhất.');
    }

    /**
     * Gợi ý 3 phương án phân bổ phòng dựa trên phòng còn trống thực tế —
     * chỉ tham khảo, KHÔNG giữ phòng hay tạo báo giá.
     */
    public function suggestOptions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'check_in'   => ['required', 'date'],
            'check_out'  => ['required', 'date', 'after:check_in'],
            'num_guests' => ['required', 'integer', 'min:1'],
        ], [], [
            'check_in'   => 'ngày nhận phòng',
            'check_out'  => 'ngày trả phòng',
            'num_guests' => 'số lượng khách',
        ]);

        $result = $this->groupBookingSuggestionService->suggest(
            $data['check_in'],
            $data['check_out'],
            $data['num_guests'],
        );

        return response()->json($result);
    }
}
