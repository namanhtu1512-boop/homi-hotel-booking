<?php

namespace Tests\Unit\Policies;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use App\Policies\RoomTypePolicy;
use Tests\TestCase;

/**
 * Unit test cho RoomTypePolicy — kiểm tra logic phân quyền thuần tuý
 * mà không cần database hay HTTP layer.
 */
class RoomTypePolicyTest extends TestCase
{
    private RoomTypePolicy $policy;
    private RoomType $roomType;
    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy   = new RoomTypePolicy();
        $this->hotel     = new Hotel(['name' => 'Test Hotel', 'status' => 'active']);
        $this->roomType = new RoomType(['name' => 'Deluxe', 'status' => 'active']);
    }

    private function makeUser(string $role): User
    {
        $user = new User();
        $user->role = $role;
        return $user;
    }

    // ----------------------------------------------------------------
    // viewAny / view
    // ----------------------------------------------------------------

    public function test_admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeUser('admin')));
    }

    public function test_staff_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeUser('staff')));
    }

    public function test_customer_cannot_view_any(): void
    {
        $this->assertFalse($this->policy->viewAny($this->makeUser('customer')));
    }

    public function test_admin_can_view(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser('admin'), $this->roomType));
    }

    public function test_staff_can_view(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser('staff'), $this->roomType));
    }

    public function test_customer_cannot_view(): void
    {
        $this->assertFalse($this->policy->view($this->makeUser('customer'), $this->roomType));
    }

    // ----------------------------------------------------------------
    // create
    // ----------------------------------------------------------------

    public function test_admin_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser('admin'), $this->hotel));
    }

    public function test_staff_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser('staff'), $this->hotel));
    }

    public function test_customer_cannot_create(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser('customer'), $this->hotel));
    }

    // ----------------------------------------------------------------
    // update
    // ----------------------------------------------------------------

    public function test_admin_can_update(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser('admin'), $this->roomType));
    }

    public function test_staff_can_update(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser('staff'), $this->roomType));
    }

    public function test_customer_cannot_update(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser('customer'), $this->roomType));
    }

    // ----------------------------------------------------------------
    // delete / restore / forceDelete
    // ----------------------------------------------------------------

    public function test_admin_can_delete(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser('admin'), $this->roomType));
    }

    public function test_staff_can_delete(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser('staff'), $this->roomType));
    }

    public function test_customer_cannot_delete(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser('customer'), $this->roomType));
    }

    public function test_admin_can_restore(): void
    {
        $this->assertTrue($this->policy->restore($this->makeUser('admin'), $this->roomType));
    }

    public function test_staff_can_restore(): void
    {
        $this->assertTrue($this->policy->restore($this->makeUser('staff'), $this->roomType));
    }

    public function test_customer_cannot_restore(): void
    {
        $this->assertFalse($this->policy->restore($this->makeUser('customer'), $this->roomType));
    }

    public function test_admin_can_force_delete(): void
    {
        $this->assertTrue($this->policy->forceDelete($this->makeUser('admin'), $this->roomType));
    }

    public function test_staff_cannot_force_delete(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->makeUser('staff'), $this->roomType));
    }

    public function test_customer_cannot_force_delete(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->makeUser('customer'), $this->roomType));
    }

    // ----------------------------------------------------------------
    // updatePrice / updateInventory / manageImages
    // ----------------------------------------------------------------

    public function test_admin_can_update_price(): void
    {
        $this->assertTrue($this->policy->updatePrice($this->makeUser('admin'), $this->roomType));
    }

    public function test_staff_can_update_price(): void
    {
        $this->assertTrue($this->policy->updatePrice($this->makeUser('staff'), $this->roomType));
    }

    public function test_customer_cannot_update_price(): void
    {
        $this->assertFalse($this->policy->updatePrice($this->makeUser('customer'), $this->roomType));
    }

    public function test_admin_can_update_inventory(): void
    {
        $this->assertTrue($this->policy->updateInventory($this->makeUser('admin'), $this->roomType));
    }

    public function test_staff_can_update_inventory(): void
    {
        $this->assertTrue($this->policy->updateInventory($this->makeUser('staff'), $this->roomType));
    }

    public function test_customer_cannot_update_inventory(): void
    {
        $this->assertFalse($this->policy->updateInventory($this->makeUser('customer'), $this->roomType));
    }

    public function test_admin_can_manage_images(): void
    {
        $this->assertTrue($this->policy->manageImages($this->makeUser('admin'), $this->roomType));
    }

    public function test_staff_can_manage_images(): void
    {
        $this->assertTrue($this->policy->manageImages($this->makeUser('staff'), $this->roomType));
    }

    public function test_customer_cannot_manage_images(): void
    {
        $this->assertFalse($this->policy->manageImages($this->makeUser('customer'), $this->roomType));
    }
}
