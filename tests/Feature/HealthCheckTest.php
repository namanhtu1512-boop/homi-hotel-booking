<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tuần 1 (BE1/BE4): route health-check dùng để xác nhận ứng dụng chạy được
 * sau khi cài đặt/deploy (README, CI, smoke test cuối kỳ đều dựa vào route
 * này).
 */
class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok_status(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJson(['status' => 'ok'])
            ->assertJsonStructure(['status', 'timestamp']);
    }

    public function test_framework_health_endpoint_is_reachable(): void
    {
        // Laravel health-check mặc định (bootstrap/app.php: health: '/up').
        $this->get('/up')->assertOk();
    }
}
