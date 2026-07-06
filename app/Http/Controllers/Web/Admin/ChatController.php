<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function index(): View
    {
        return view('chat.inbox', [
            'inbox'  => $this->chatService->inboxList(),
            'layout' => 'layouts.admin',
            'showRoute' => 'admin.chat.show',
        ]);
    }

    public function show(int $customerId): View
    {
        $customer = User::where('role', 'customer')->findOrFail($customerId);

        $this->chatService->markReadForStaff($customerId);

        return view('chat.thread', [
            'customer'   => $customer,
            'messages'   => $this->chatService->messagesForCustomer($customerId),
            'layout'     => 'layouts.admin',
            'backRoute'  => route('admin.chat.index'),
            'formAction' => route('admin.chat.store', $customerId),
            'pollRoute'  => route('admin.chat.poll', $customerId),
        ]);
    }

    public function store(Request $request, int $customerId): RedirectResponse
    {
        User::where('role', 'customer')->findOrFail($customerId);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [], ['body' => 'nội dung tin nhắn']);

        $this->chatService->send($customerId, $request->user(), $data['body']);

        return redirect()->route('admin.chat.show', $customerId);
    }

    public function poll(Request $request, int $customerId): JsonResponse
    {
        User::where('role', 'customer')->findOrFail($customerId);

        $this->chatService->markReadForStaff($customerId);

        $messages = $this->chatService->messagesForCustomer($customerId, $request->integer('after') ?: null);

        return response()->json([
            // "is_mine" ở đây nghĩa là "phía nhân viên/admin" (không phải
            // đúng người đang đăng nhập) — hộp thư dùng chung, mọi tin từ
            // admin/staff đều hiện cùng 1 bên, không phân biệt ai gửi.
            'messages' => $messages->map(fn ($m) => [
                'id'         => $m->id,
                'body'       => $m->body,
                'is_mine'    => $m->sender_id !== $customerId,
                'sender'     => $m->sender?->name,
                'created_at' => $m->created_at->format('H:i d/m'),
            ]),
        ]);
    }
}
