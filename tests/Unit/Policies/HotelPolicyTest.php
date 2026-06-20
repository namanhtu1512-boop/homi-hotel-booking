<?php

namespace Tests\Unit\Policies;

use App\Models\HotelInfo;
use App\Models\User;
use App\Policies\HotelPolicy;
use Tests\TestCase;

/**
 * Unit test cho HotelPolicy — kiểm soát quyền xem/sửa thông tin khách sạn singleton.
 */
class HotelPolicyTest extends TestCase
{
    private HotelPolicy $policy;
    private HotelInfo $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new HotelPolicy();
        $this->hotel = new HotelInfo(['name' => 'Test Hotel']);
    }

    private function makeUser(string $role): User
    {
        $user = new User();
        $user->role = $role;
        return $user;
    }

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

    public function test_admin_can_manage_images(): void
    {
        $this->assertTrue($this->policy->manageImages($this->makeUser('admin'), $this->hotel));
    }

    public function test_customer_cannot_manage_images(): void
    {
        $this->assertFalse($this->policy->manageImages($this->makeUser('customer'), $this->hotel));
    }
}
