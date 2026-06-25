<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

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

        if (Auth::user()->status !== 'active') {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản đang bị khóa hoặc chưa hoạt động.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectByRole($request->user());
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
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'staff'])) {
            return redirect()->route('admin.dashboard');
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

        if (!in_array($user->role, ['admin', 'staff'])) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tài khoản này không có quyền truy cập khu vực quản trị.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function adminLogout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
