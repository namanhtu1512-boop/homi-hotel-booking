@extends('layouts.admin')

@section('title', 'Cài đặt hệ thống · Homi Admin')

@php
    $paymentMethods = explode(',', $settings['payment_methods'] ?? 'cod,bank_transfer');
@endphp

@section('content')
    <div class="admin-page-header">
        <div><h1>⚙️ Cài đặt hệ thống</h1><p>Cấu hình thông tin chung, thanh toán, thông báo và bảo mật</p></div>
    </div>

    <div class="admin-tabs">
        <div class="admin-tab active" onclick="switchSettingsTab('general',this)">Thông tin chung</div>
        <div class="admin-tab" onclick="switchSettingsTab('payment',this)">Thanh toán</div>
        <div class="admin-tab" onclick="switchSettingsTab('notification',this)">Thông báo</div>
        <div class="admin-tab" onclick="switchSettingsTab('security',this)">Bảo mật</div>
    </div>

    <div class="card">
        <div class="card-body">

            <form id="settings-general" method="POST" action="{{ route('admin.settings.general') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Tên website</label><input class="form-control" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? 'Homi Hotel Booking') }}"></div>
                    <div class="form-group"><label class="form-label">Hotline</label><input class="form-control" name="hotline" value="{{ old('hotline', $settings['hotline'] ?? '1900 1234') }}"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Email liên hệ</label><input class="form-control" type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? 'support@homi.vn') }}"></div>
                    <div class="form-group"><label class="form-label">Địa chỉ trụ sở</label><input class="form-control" name="address" value="{{ old('address', $settings['address'] ?? 'Quận 1, TP. Hồ Chí Minh') }}"></div>
                </div>
                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
            </form>

            <form id="settings-payment" method="POST" action="{{ route('admin.settings.payment') }}" style="display:none">
                @csrf
                <div class="form-group">
                    <label class="form-label">Phương thức thanh toán chấp nhận</label>
                    <div class="check-grid">
                        <label class="check-item"><input type="checkbox" name="payment_methods[]" value="cod" @checked(in_array('cod', $paymentMethods))> Thanh toán khi nhận phòng (COD)</label>
                        <label class="check-item"><input type="checkbox" name="payment_methods[]" value="bank_transfer" @checked(in_array('bank_transfer', $paymentMethods))> Chuyển khoản ngân hàng</label>
                        <label class="check-item"><input type="checkbox" name="payment_methods[]" value="vnpay" @checked(in_array('vnpay', $paymentMethods))> VNPay</label>
                        <label class="check-item"><input type="checkbox" name="payment_methods[]" value="momo" @checked(in_array('momo', $paymentMethods))> Momo</label>
                    </div>
                </div>
                <div class="form-group" style="max-width:240px">
                    <label class="form-label">Phí dịch vụ (%)</label>
                    <input class="form-control" type="number" name="service_fee_percent" value="{{ old('service_fee_percent', $settings['service_fee_percent'] ?? 5) }}" min="0" max="100">
                </div>
                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
            </form>

            <form id="settings-notification" method="POST" action="{{ route('admin.settings.notification') }}" style="display:none">
                @csrf
                <div class="switch-row">
                    <div><strong>Email khi có đặt phòng mới</strong><span>Gửi email cho quản trị viên khi phát sinh đơn mới</span></div>
                    <label class="switch"><input type="checkbox" name="notify_new_booking" value="1" @checked(($settings['notify_new_booking'] ?? '1') === '1')><span class="slider"></span></label>
                </div>
                <div class="switch-row">
                    <div><strong>Email khi đặt phòng bị hủy</strong><span>Thông báo ngay khi khách hủy đơn</span></div>
                    <label class="switch"><input type="checkbox" name="notify_cancelled_booking" value="1" @checked(($settings['notify_cancelled_booking'] ?? '1') === '1')><span class="slider"></span></label>
                </div>
                <div class="switch-row">
                    <div><strong>SMS xác nhận đặt phòng</strong><span>Gửi SMS xác nhận tới khách hàng</span></div>
                    <label class="switch"><input type="checkbox" name="notify_sms_confirmation" value="1" @checked(($settings['notify_sms_confirmation'] ?? '0') === '1')><span class="slider"></span></label>
                </div>
                <button type="submit" class="btn btn-primary mt-2">💾 Lưu thay đổi</button>
            </form>

            <div id="settings-security" style="display:none">
                <form method="POST" action="{{ route('admin.settings.password') }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Mật khẩu hiện tại</label><input class="form-control" type="password" name="current_password" placeholder="••••••••"></div>
                        <div></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Mật khẩu mới</label><input class="form-control" type="password" name="new_password" placeholder="••••••••"></div>
                        <div class="form-group"><label class="form-label">Xác nhận mật khẩu mới</label><input class="form-control" type="password" name="new_password_confirmation" placeholder="••••••••"></div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">🔑 Đổi mật khẩu</button>
                </form>

                <hr class="divider">

                <form method="POST" action="{{ route('admin.settings.security') }}">
                    @csrf
                    <div class="switch-row">
                        <div><strong>Xác thực 2 lớp (2FA)</strong><span>Tăng bảo mật cho tài khoản quản trị</span></div>
                        <label class="switch"><input type="checkbox" name="two_factor_enabled" value="1" @checked(($settings['two_factor_enabled'] ?? '0') === '1')><span class="slider"></span></label>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">💾 Lưu thay đổi</button>
                </form>
            </div>

        </div>
    </div>
@endsection
