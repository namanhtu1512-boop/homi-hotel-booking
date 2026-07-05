<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\ContactMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function __construct(private readonly ContactMessageService $contactMessageService) {}

    public function index(Request $request): View
    {
        return view('admin.contact-messages.index', [
            'messages' => $this->contactMessageService->adminList($request->only('status')),
            'filters'  => $request->only('status'),
        ]);
    }

    public function markRead(int $id): RedirectResponse
    {
        $message = ContactMessage::findOrFail($id);

        $this->contactMessageService->markRead($message);

        return redirect()->route('admin.contact-messages.index')->with('success', 'Đã đánh dấu đã đọc.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $message = ContactMessage::findOrFail($id);

        $this->contactMessageService->delete($message);

        return redirect()->route('admin.contact-messages.index')->with('success', 'Đã xóa liên hệ.');
    }
}
