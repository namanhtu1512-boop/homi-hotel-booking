<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangeEmailRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('customer.profile', ['user' => auth()->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'phone', 'address']);

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $request->user()->update($data);

        return redirect()
            ->route('customer.profile.show')
            ->with('success', 'Cập nhật thông tin thành công.');
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }

        $user->update(['password' => $request->validated('password')]);

        return redirect()
            ->route('customer.profile.show')
            ->with('success', 'Đổi mật khẩu thành công.');
    }

    public function updateEmail(ChangeEmailRequest $request): RedirectResponse
    {
        $request->user()->update(['email' => $request->validated('email')]);

        return redirect()
            ->route('customer.profile.show')
            ->with('success', 'Đổi email thành công.');
    }
}
