<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    /**
     * Không còn trang dashboard riêng — các chức năng (đặt phòng, đơn của
     * tôi, tài khoản) đã được đưa vào thanh điều hướng chung. Route này giữ
     * lại để làm đích chuyển hướng sau đăng nhập/đăng ký và cho middleware
     * phân quyền, chỉ việc đưa khách về trang chủ.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('home');
    }
}
