<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationPollController extends Controller
{
    public function poll(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        $unread = $user->unreadNotifications()->latest()->take(10)->get();

        return response()->json([
            'count' => $unread->count(),
            'items' => $unread->map(fn ($n) => [
                'id'      => $n->id,
                'message' => $n->data['message'] ?? '',
                'url'     => $n->data['url'] ?? '#',
                'ago'     => $n->created_at->diffForHumans(),
            ]),
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
