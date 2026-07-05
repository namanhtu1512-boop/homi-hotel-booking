<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ContactMessageService;
use App\Services\HotelInfoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactMessageService $contactMessageService,
        private readonly HotelInfoService $hotelInfoService,
    ) {}

    public function show(): View
    {
        return view('client.contact', ['hotel' => $this->hotelInfoService->current()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:2000'],
        ], [], [
            'name'    => 'họ tên',
            'email'   => 'email',
            'phone'   => 'số điện thoại',
            'message' => 'nội dung',
        ]);

        $this->contactMessageService->create($data);

        return redirect()
            ->route('contact.show')
            ->with('success', 'Đã gửi liên hệ thành công! Homi sẽ phản hồi sớm nhất.');
    }
}
