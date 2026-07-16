<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationPollController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function poll(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        $unread = $user->unreadNotifications()->latest()->take(10)->get();

        // Badge "Chat khách hàng" ở sidebar admin/staff trước đây chỉ tính
        // lúc render trang (view composer) nên không tự cập nhật khi có tin
        // nhắn mới đến trong lúc đang ở trang khác — gộp luôn vào poll chung
        // (đã chạy sẵn mỗi 30s) thay vì thêm 1 endpoint poll riêng.
        $chatUnread = in_array($user->role, ['admin', 'staff'], true)
            ? $this->chatService->unreadCountForStaff()
            : null;

        return response()->json([
            'count'       => $unread->count(),
            'items'       => $unread->map(fn ($n) => [
                'id'      => $n->id,
                'message' => $n->data['message'] ?? '',
                'url'     => $n->data['url'] ?? '#',
                'ago'     => $n->created_at->diffForHumans(),
            ]),
            'chat_unread' => $chatUnread,
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $id   = $request->input('id');

        if ($id) {
            $user->notifications()->find($id)?->markAsRead();
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['ok' => true]);
    }
}
