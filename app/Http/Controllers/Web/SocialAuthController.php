<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Khớp theo (provider, provider_id) trước — nếu chưa từng đăng nhập social
     * lần nào thì thử liên kết vào tài khoản thường đã có cùng email (khách
     * từng đăng ký bằng email/mật khẩu, nay đăng nhập lại qua social); nếu
     * email cũng chưa tồn tại thì tạo tài khoản mới, coi như đã xác thực email
     * (provider đã xác thực hộ).
     */
    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException $e) {
            return redirect()->route('login')->withErrors(['email' => 'Phiên đăng nhập đã hết hạn, vui lòng thử lại.']);
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email' => 'Không thể đăng nhập bằng ' . ucfirst($provider) . ', vui lòng thử lại.']);
        }

        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'provider'    => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            }
        }

        if (! $user) {
            $user = User::create([
                'name'              => $socialUser->getName() ?: $socialUser->getNickname() ?: 'Khách hàng',
                'email'             => $socialUser->getEmail(),
                'password'          => Hash::make(Str::random(40)),
                'provider'          => $provider,
                'provider_id'       => $socialUser->getId(),
                'avatar_path'       => $socialUser->getAvatar(),
                'role'              => 'customer',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]);
        }

        if ($user->status !== 'active') {
            return redirect()->route('login')->withErrors(['email' => 'Tài khoản đang bị khóa hoặc chưa hoạt động.']);
        }

        if (in_array($user->role, ['admin', 'staff'], true)) {
            return redirect()->route('login')->withErrors(['email' => 'Tài khoản quản trị/staff vui lòng đăng nhập tại khu vực quản trị.']);
        }

        Auth::login($user);
        request()->session()->regenerate();
        request()->session()->put('login_context', 'customer');

        return redirect()->route('customer.dashboard');
    }
}
