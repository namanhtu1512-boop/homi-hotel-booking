<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthWebController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('login_context', 'customer');

        return redirect()->route('customer.dashboard');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($data)) {
            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
                ->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản đang bị khóa hoặc chưa hoạt động.'])
                ->onlyInput('email');
        }

        // Đối xứng với adminLogin() — chặn admin/staff đăng nhập nhầm qua form
        // khách hàng ngay tại đây, thay vì để RoleMiddleware dội qua dội lại
        // giữa customer.dashboard và admin.login (đăng nhập "thành công" nhưng
        // không vào được đâu, không rõ lý do).
        if (in_array($user->role, ['admin', 'staff'], true)) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản quản trị/staff vui lòng đăng nhập tại khu vực quản trị.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('login_context', 'customer');

        return redirect()->route('customer.dashboard');
    }

    /**
     * Đích chuyển hướng sau đăng nhập — admin và staff có khu vực làm việc
     * riêng biệt (/admin/* và /staff/*), không dùng chung dashboard.
     */
    private function redirectByRole(User $user): RedirectResponse
    {
        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'staff' => redirect()->route('staff.dashboard'),
            default => redirect()->route('customer.dashboard'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showAdminLogin()
    {
        if (Auth::check() && Auth::user()->role === 'admin' && session('login_context') === 'admin') {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.admin-login');
    }

    public function adminLogin(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($data)) {
            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
                ->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản đang bị khóa hoặc chưa hoạt động.'])
                ->onlyInput('email');
        }

        // Khu vực admin và staff tách biệt hoàn toàn — staff đăng nhập nhầm
        // ở đây được hướng sang đúng cổng thay vì bị chặn không rõ lý do.
        if ($user->role === 'staff') {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản nhân viên vui lòng đăng nhập tại khu vực nhân viên.'])
                ->onlyInput('email');
        }

        if ($user->role !== 'admin') {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản này không có quyền truy cập khu vực quản trị.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('login_context', 'admin');

        return $this->redirectByRole($user);
    }

    public function adminLogout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function showStaffLogin()
    {
        if (Auth::check() && Auth::user()->role === 'staff' && session('login_context') === 'staff') {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.staff-login');
    }

    public function staffLogin(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($data)) {
            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
                ->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản đang bị khóa hoặc chưa hoạt động.'])
                ->onlyInput('email');
        }

        if ($user->role === 'admin') {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản quản trị viên vui lòng đăng nhập tại khu vực quản trị.'])
                ->onlyInput('email');
        }

        if ($user->role !== 'staff') {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản này không có quyền truy cập khu vực nhân viên.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('login_context', 'staff');

        return $this->redirectByRole($user);
    }

    public function staffLogout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}
