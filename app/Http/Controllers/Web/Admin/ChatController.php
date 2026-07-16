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

    public function store(Request $request, int $customerId): JsonResponse|RedirectResponse
    {
        User::where('role', 'customer')->findOrFail($customerId);

        $data = $request->validate([
            'body'  => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ], [], ['body' => 'nội dung tin nhắn']);

        if (empty($data['body']) && ! $request->hasFile('image')) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Vui lòng nhập tin nhắn hoặc chọn ảnh.'], 422)
                : back()->withErrors(['body' => 'Vui lòng nhập tin nhắn hoặc chọn ảnh.']);
        }

        $imagePath = $request->hasFile('image') ? $request->file('image')->store('chat', 'public') : null;
        $msg = $this->chatService->send($customerId, $request->user(), $data['body'] ?? '', $imagePath);
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

        return redirect()->route('admin.chat.show', $customerId);
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
                'image_url'  => $m->image_path ? asset('storage/' . $m->image_path) : null,
                'is_mine'    => $m->sender_id !== $customerId,
                'sender'     => $m->sender?->name,
                'created_at' => $m->created_at->format('H:i d/m'),
            ]),
        ]);
    }
}
