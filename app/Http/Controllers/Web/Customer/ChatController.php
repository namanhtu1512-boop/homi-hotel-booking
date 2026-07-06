<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function index(Request $request): View
    {
        $customerId = $request->user()->id;

        $this->chatService->markReadForCustomer($customerId);

        return view('customer.chat.index', [
            'messages' => $this->chatService->messagesForCustomer($customerId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [], ['body' => 'nội dung tin nhắn']);

        $this->chatService->send($request->user()->id, $request->user(), $data['body']);

        return redirect()->route('customer.chat.index');
    }

    public function poll(Request $request): JsonResponse
    {
        $customerId = $request->user()->id;

        $this->chatService->markReadForCustomer($customerId);

        $messages = $this->chatService->messagesForCustomer($customerId, $request->integer('after') ?: null);

        return response()->json([
            'messages' => $messages->map(fn ($m) => [
                'id'         => $m->id,
                'body'       => $m->body,
                'is_mine'    => $m->sender_id === $customerId,
                'sender'     => $m->sender?->name,
                'created_at' => $m->created_at->format('H:i d/m'),
            ]),
        ]);
    }
}
