<?php

namespace App\Providers;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Gate: chỉ admin
        Gate::define('admin-only', fn(User $user) => $user->role === 'admin');

        // Gate: admin hoặc staff
        Gate::define('admin-or-staff', fn(User $user) => in_array($user->role, ['admin', 'staff']));

        // Gate: chỉ customer
        Gate::define('customer-only', fn(User $user) => $user->role === 'customer');

        // Gate: tài khoản đang hoạt động (dùng để kiểm tra trước khi thực hiện thao tác nhạy cảm)
        Gate::define('active-account', fn(User $user) => $user->status === 'active');

        // Morph map: lưu alias ngắn gọn thay vì full class name trong cột polymorphic
        // (vd: audit_logs.auditable_type), tránh vỡ dữ liệu cũ khi đổi namespace.
        Relation::enforceMorphMap([
            'users'      => User::class,
            'hotels'     => Hotel::class,
            'room_types' => RoomType::class,
        ]);
    }
}
