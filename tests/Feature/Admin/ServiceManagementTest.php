<?php

namespace Tests\Feature\Admin;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quản lý dịch vụ thêm (admin CRUD, admin-only giống Promotions/News/Banners)
 * — xem ServiceController.
 */
class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'        => 'Ăn sáng buffet',
            'description' => 'Buffet sáng tại nhà hàng.',
            'price'       => 150000,
            'status'      => 'active',
        ], $overrides);
    }

    public function test_admin_can_view_services_list(): void
    {
        $admin = $this->makeUser('admin');
        Service::factory()->create();

        $this->actingAsAdmin($admin)->get('/admin/services')->assertOk();
    }

    public function test_staff_cannot_access_admin_services(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)
            ->get('/admin/services')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_access_admin_services(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->get('/admin/services')
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_admin_can_create_service(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)
            ->post('/admin/services', $this->payload())
            ->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseHas('services', ['name' => 'Ăn sáng buffet', 'price' => 150000]);
    }

    public function test_admin_can_update_service(): void
    {
        $admin   = $this->makeUser('admin');
        $service = Service::factory()->create();

        $this->actingAsAdmin($admin)
            ->put("/admin/services/{$service->id}", $this->payload(['name' => 'Đã sửa']))
            ->assertRedirect(route('admin.services.index'));

        $this->assertSame('Đã sửa', $service->fresh()->name);
    }

    public function test_admin_can_soft_delete_and_restore_service(): void
    {
        $admin   = $this->makeUser('admin');
        $service = Service::factory()->create();

        $this->actingAsAdmin($admin)
            ->delete("/admin/services/{$service->id}")
            ->assertRedirect(route('admin.services.index'));

        $this->assertSoftDeleted('services', ['id' => $service->id]);

        $this->actingAsAdmin($admin)
            ->post("/admin/services/{$service->id}/restore")
            ->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseHas('services', ['id' => $service->id, 'deleted_at' => null]);
    }

    public function test_create_service_fails_without_name(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)
            ->post('/admin/services', $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_create_service_fails_with_negative_price(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)
            ->post('/admin/services', $this->payload(['price' => -10000]))
            ->assertSessionHasErrors('price');
    }
}
