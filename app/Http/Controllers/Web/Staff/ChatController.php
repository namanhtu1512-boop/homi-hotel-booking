<?php

namespace App\Http\Controllers\Web\Staff;

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
            'inbox'     => $this->chatService->inboxList(),
            'layout'    => 'layouts.staff',
            'showRoute' => 'staff.chat.show',
        ]);
    }

    public function show(int $customerId): View
    {
        $customer = User::where('role', 'customer')->findOrFail($customerId);

        $this->chatService->markReadForStaff($customerId);

        return view('chat.thread', [
            'customer'   => $customer,
            'messages'   => $this->chatService->messagesForCustomer($customerId),
            'layout'     => 'layouts.staff',
            'backRoute'  => route('staff.chat.index'),
            'formAction' => route('staff.chat.store', $customerId),
            'pollRoute'  => route('staff.chat.poll', $customerId),
        ]);
    }

    public function store(Request $request, int $customerId): RedirectResponse
    {
        User::where('role', 'customer')->findOrFail($customerId);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [], ['body' => 'nội dung tin nhắn']);

        $this->chatService->send($customerId, $request->user(), $data['body']);

        return redirect()->route('staff.chat.show', $customerId);
    }

    public function poll(Request $request, int $customerId): JsonResponse
    {
        User::where('role', 'customer')->findOrFail($customerId);

        $this->chatService->markReadForStaff($customerId);

        $messages = $this->chatService->messagesForCustomer($customerId, $request->integer('after') ?: null);

        return response()->json([
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
