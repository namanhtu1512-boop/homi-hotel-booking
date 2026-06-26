<?php

namespace Tests\Unit\Policies;

use App\Models\HotelInfo;
use App\Models\User;
use App\Policies\HotelInfoPolicy;
use Tests\TestCase;

/**
 * Unit test cho HotelInfoPolicy — kiểm tra logic phân quyền thuần tuý
 * mà không cần database hay HTTP layer. hotel_info là singleton nên
 * policy chỉ còn view/update/toggleStatus/manageImages (không có
 * create/delete/restore/forceDelete).
 */
class HotelInfoPolicyTest extends TestCase
{
    private HotelInfoPolicy $policy;
    private HotelInfo $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new HotelInfoPolicy();
        $this->hotel  = new HotelInfo(['name' => 'Test Hotel', 'status' => 'active']);
    }

    private function makeUser(string $role): User
    {
        $user = new User();
        $user->role = $role;
        return $user;
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
