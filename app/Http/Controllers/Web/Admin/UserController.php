<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.users.index', [
            'users'  => $users,
            'role'   => $request->input('role', ''),
            'search' => $request->input('search', ''),
        ]);
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Không thể khóa tài khoản của chính mình.');
        }

        $user->update([
            'status' => $user->status === 'active' ? 'locked' : 'active',
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Đã chuyển tài khoản \"{$user->name}\" sang trạng thái \"{$user->status}\".");
    }
}
