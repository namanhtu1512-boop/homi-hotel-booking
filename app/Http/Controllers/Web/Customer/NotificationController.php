<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markRead(Request $request): RedirectResponse
    {
        $id = $request->input('id');

        $query = Auth::user()->notifications();
        $notif = $id ? $query->find($id) : null;

        if ($notif) {
            $notif->markAsRead();
            return redirect($notif->data['url'] ?? route('customer.dashboard'));
        }

        // Đánh dấu tất cả
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    }
}
