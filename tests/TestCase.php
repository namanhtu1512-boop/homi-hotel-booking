<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Login admin/staff qua web session (có login_context: admin).
     * Dùng cho tất cả test truy cập web admin routes.
     */
    protected function actingAsAdmin(User $user): static
    {
        return $this->actingAs($user)->withSession(['login_context' => 'admin']);
    }
}
