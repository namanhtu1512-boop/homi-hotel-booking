<?php

namespace Tests\Unit\Policies;

use App\Models\Hotel;
use App\Models\User;
use App\Policies\HotelPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit test cho HotelPolicy — kiểm tra logic phân quyền thuần tuý
 * mà không cần database hay HTTP layer.
 */
class HotelPolicyTest extends TestCase
{
    private HotelPolicy $policy;
    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new HotelPolicy();

        // Hotel stub — không cần persist vào DB
        $this->hotel = new Hotel(['name' => 'Test Hotel', 'status' => 'active']);
    }

    private function makeUser(string $role): User
    {
        $user = new User();
        $user->role = $role;
        return $user;
    }

    // ----------------------------------------------------------------
    // viewAny
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

    // ----------------------------------------------------------------
    // view
    // ----------------------------------------------------------------

    public function test_admin_can_view(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser('admin'), $this->hotel));
    }

    public function test_staff_can_view(): void
    {
        $this->assertTrue($this->policy->view($this->makeUser('staff'), $this->hotel));
    }

    public function test_customer_cannot_view(): void
    {
        $this->assertFalse($this->policy->view($this->makeUser('customer'), $this->hotel));
    }

    // ----------------------------------------------------------------
    // create
    // ----------------------------------------------------------------

    public function test_admin_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser('admin')));
    }

    public function test_staff_can_create(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser('staff')));
    }

    public function test_customer_cannot_create(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser('customer')));
    }

    // ----------------------------------------------------------------
    // update
    // ----------------------------------------------------------------

    public function test_admin_can_update(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser('admin'), $this->hotel));
    }

    public function test_staff_can_update(): void
    {
        $this->assertTrue($this->policy->update($this->makeUser('staff'), $this->hotel));
    }

    public function test_customer_cannot_update(): void
    {
        $this->assertFalse($this->policy->update($this->makeUser('customer'), $this->hotel));
    }

    // ----------------------------------------------------------------
    // delete (soft)
    // ----------------------------------------------------------------

    public function test_admin_can_delete(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser('admin'), $this->hotel));
    }

    public function test_staff_can_delete(): void
    {
        $this->assertTrue($this->policy->delete($this->makeUser('staff'), $this->hotel));
    }

    public function test_customer_cannot_delete(): void
    {
        $this->assertFalse($this->policy->delete($this->makeUser('customer'), $this->hotel));
    }

    // ----------------------------------------------------------------
    // restore
    // ----------------------------------------------------------------

    public function test_admin_can_restore(): void
    {
        $this->assertTrue($this->policy->restore($this->makeUser('admin'), $this->hotel));
    }

    public function test_staff_can_restore(): void
    {
        $this->assertTrue($this->policy->restore($this->makeUser('staff'), $this->hotel));
    }

    public function test_customer_cannot_restore(): void
    {
        $this->assertFalse($this->policy->restore($this->makeUser('customer'), $this->hotel));
    }

    // ----------------------------------------------------------------
    // forceDelete
    // ----------------------------------------------------------------

    public function test_admin_can_force_delete(): void
    {
        $this->assertTrue($this->policy->forceDelete($this->makeUser('admin'), $this->hotel));
    }

    public function test_staff_cannot_force_delete(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->makeUser('staff'), $this->hotel));
    }

    public function test_customer_cannot_force_delete(): void
    {
        $this->assertFalse($this->policy->forceDelete($this->makeUser('customer'), $this->hotel));
    }

    // ----------------------------------------------------------------
    // toggleStatus
    // ----------------------------------------------------------------

    public function test_admin_can_toggle_status(): void
    {
        $this->assertTrue($this->policy->toggleStatus($this->makeUser('admin'), $this->hotel));
    }

    public function test_staff_can_toggle_status(): void
    {
        $this->assertTrue($this->policy->toggleStatus($this->makeUser('staff'), $this->hotel));
    }

    public function test_customer_cannot_toggle_status(): void
    {
        $this->assertFalse($this->policy->toggleStatus($this->makeUser('customer'), $this->hotel));
    }

    // ----------------------------------------------------------------
    // manageImages
    // ----------------------------------------------------------------

    public function test_admin_can_manage_images(): void
    {
        $this->assertTrue($this->policy->manageImages($this->makeUser('admin'), $this->hotel));
    }

    public function test_staff_can_manage_images(): void
    {
        $this->assertTrue($this->policy->manageImages($this->makeUser('staff'), $this->hotel));
    }

    public function test_customer_cannot_manage_images(): void
    {
        $this->assertFalse($this->policy->manageImages($this->makeUser('customer'), $this->hotel));
    }
}
