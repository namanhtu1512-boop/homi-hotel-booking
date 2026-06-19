<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        $settings = $this->settingsService->all();

        return view('admin.settings.index', compact('settings'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name'     => ['required', 'string', 'max:150'],
            'hotline'       => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'address'       => ['nullable', 'string', 'max:255'],
        ]);

        $this->settingsService->setMany($data);
        $this->auditLog->log('settings.updated', null, 'Cập nhật thông tin chung hệ thống.');

        return redirect()->route('admin.settings.index')->with('success', 'Đã lưu thông tin chung.');
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_methods'        => ['nullable', 'array'],
            'payment_methods.*'      => ['string'],
            'service_fee_percent'    => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $this->settingsService->setMany([
            'payment_methods'     => implode(',', $data['payment_methods'] ?? []),
            'service_fee_percent' => $data['service_fee_percent'],
        ]);
        $this->auditLog->log('settings.updated', null, 'Cập nhật cài đặt thanh toán.');

        return redirect()->route('admin.settings.index')->with('success', 'Đã lưu cài đặt thanh toán.');
    }

    public function updateNotification(Request $request): RedirectResponse
    {
        $this->settingsService->setMany([
            'notify_new_booking'      => $request->boolean('notify_new_booking') ? '1' : '0',
            'notify_cancelled_booking' => $request->boolean('notify_cancelled_booking') ? '1' : '0',
            'notify_sms_confirmation' => $request->boolean('notify_sms_confirmation') ? '1' : '0',
        ]);
        $this->auditLog->log('settings.updated', null, 'Cập nhật cài đặt thông báo.');

        return redirect()->route('admin.settings.index')->with('success', 'Đã lưu cài đặt thông báo.');
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $this->settingsService->setMany([
            'two_factor_enabled' => $request->boolean('two_factor_enabled') ? '1' : '0',
        ]);
        $this->auditLog->log('settings.updated', null, 'Cập nhật cài đặt bảo mật.');

        return redirect()->route('admin.settings.index')->with('success', 'Đã lưu cài đặt bảo mật.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'current_password' => 'mật khẩu hiện tại',
            'new_password'     => 'mật khẩu mới',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return redirect()
                ->route('admin.settings.index')
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }

        $user->update(['password' => Hash::make($request->input('new_password'))]);
        $this->auditLog->log('user.password_changed', $user, 'Đổi mật khẩu tài khoản của chính mình.');

        return redirect()->route('admin.settings.index')->with('success', 'Đã đổi mật khẩu thành công.');
    }
}
