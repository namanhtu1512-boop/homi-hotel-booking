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

    public function store(Request $request): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'body'  => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ], [], ['body' => 'nội dung tin nhắn']);

        if (empty($data['body']) && ! $request->hasFile('image')) {
            $err = ['body' => 'Vui lòng nhập tin nhắn hoặc chọn ảnh.'];
            return $request->wantsJson()
                ? response()->json(['error' => $err['body']], 422)
                : back()->withErrors($err);
        }

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('chat', 'public')
            : null;

        $msg = $this->chatService->send($request->user()->id, $request->user(), $data['body'] ?? '', $imagePath);
        $msg->load('sender');

        if ($request->wantsJson()) {
            return response()->json([
                'id'         => $msg->id,
                'body'       => $msg->body,
                'image_url'  => $msg->image_path ? asset('storage/' . $msg->image_path) : null,
                'is_mine'    => true,
                'sender'     => $msg->sender?->name,
                'created_at' => $msg->created_at->format('H:i d/m'),
            ]);
        }

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
                'image_url'  => $m->image_path ? asset('storage/' . $m->image_path) : null,
                'is_mine'    => $m->sender_id === $customerId,
                'sender'     => $m->sender?->name,
                'created_at' => $m->created_at->format('H:i d/m'),
            ]),
        ]);
    }
}
