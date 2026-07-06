<?php

namespace Tests\Feature\Admin;

use App\Models\RoomType;
use App\Models\SeasonalRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quản lý bảng giá theo mùa (admin CRUD) — xem SeasonalRateController.
 */
class SeasonalRateManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'label'            => 'Tết Nguyên Đán',
            'room_type_id'     => '',
            'start_date'       => now()->addDays(60)->toDateString(),
            'end_date'         => now()->addDays(67)->toDateString(),
            'adjustment_type'  => 'percent',
            'adjustment_value' => 30,
            'status'           => 'active',
        ], $overrides);
    }

    public function test_admin_can_view_seasonal_rates_list(): void
    {
        $admin = $this->makeUser('admin');
        SeasonalRate::factory()->create();

        $this->actingAsAdmin($admin)->get('/admin/seasonal-rates')->assertOk();
    }

    public function test_staff_cannot_access_admin_seasonal_rates(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)
            ->get('/admin/seasonal-rates')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_access_admin_seasonal_rates(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->get('/admin/seasonal-rates')
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_admin_can_create_seasonal_rate(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)
            ->post('/admin/seasonal-rates', $this->payload())
            ->assertRedirect(route('admin.seasonal-rates.index'));

        $this->assertDatabaseHas('seasonal_rates', ['label' => 'Tết Nguyên Đán']);
    }

    public function test_admin_can_update_seasonal_rate(): void
    {
        $admin = $this->makeUser('admin');
        $rate  = SeasonalRate::factory()->create();

        $this->actingAsAdmin($admin)
            ->put("/admin/seasonal-rates/{$rate->id}", $this->payload(['label' => 'Đã sửa']))
            ->assertRedirect(route('admin.seasonal-rates.index'));

        $this->assertSame('Đã sửa', $rate->fresh()->label);
    }

    public function test_admin_can_delete_seasonal_rate(): void
    {
        $admin = $this->makeUser('admin');
        $rate  = SeasonalRate::factory()->create();

        $this->actingAsAdmin($admin)
            ->delete("/admin/seasonal-rates/{$rate->id}")
            ->assertRedirect(route('admin.seasonal-rates.index'));

        $this->assertDatabaseMissing('seasonal_rates', ['id' => $rate->id]);
    }

    public function test_create_seasonal_rate_fails_without_label(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)
            ->post('/admin/seasonal-rates', $this->payload(['label' => '']))
            ->assertSessionHasErrors('label');
    }

    public function test_overlapping_active_rate_in_same_scope_is_rejected(): void
    {
        $admin    = $this->makeUser('admin');
        $roomType = RoomType::factory()->create();

        SeasonalRate::factory()->create([
            'room_type_id' => $roomType->id,
            'start_date'   => now()->addDays(60)->toDateString(),
            'end_date'     => now()->addDays(70)->toDateString(),
        ]);

        $response = $this->actingAsAdmin($admin)->post('/admin/seasonal-rates', $this->payload([
            'room_type_id' => $roomType->id,
            'start_date'   => now()->addDays(65)->toDateString(),
            'end_date'     => now()->addDays(75)->toDateString(),
        ]));

        $response->assertSessionHasErrors('start_date');
    }

    public function test_overlapping_rate_for_different_room_type_is_allowed(): void
    {
        $admin     = $this->makeUser('admin');
        $roomTypeA = RoomType::factory()->create();
        $roomTypeB = RoomType::factory()->create();

        SeasonalRate::factory()->create([
            'room_type_id' => $roomTypeA->id,
            'start_date'   => now()->addDays(60)->toDateString(),
            'end_date'     => now()->addDays(70)->toDateString(),
        ]);

        $response = $this->actingAsAdmin($admin)->post('/admin/seasonal-rates', $this->payload([
            'room_type_id' => $roomTypeB->id,
            'start_date'   => now()->addDays(60)->toDateString(),
            'end_date'     => now()->addDays(70)->toDateString(),
        ]));

        $response->assertSessionDoesntHaveErrors();
    }
}
