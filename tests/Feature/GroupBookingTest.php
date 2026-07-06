<?php

namespace Tests\Feature;

use App\Models\GroupBookingRequest;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Đặt đoàn/nhóm — form gửi yêu cầu công khai + admin quản lý (mark
 * contacted/xóa), tương tự luồng ContactMessage nhưng có thêm số lượng
 * khách/ngày dự kiến/loại phòng quan tâm.
 */
class GroupBookingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'contact_name' => 'Nguyễn Văn A',
            'email'        => 'a@example.com',
            'group_size'   => 10,
        ], $overrides);
    }

    public function test_guest_can_submit_group_booking_request(): void
    {
        $this->post('/group-bookings', $this->payload())->assertRedirect();

        $this->assertDatabaseHas('group_booking_requests', [
            'contact_name' => 'Nguyễn Văn A',
            'email'        => 'a@example.com',
            'group_size'   => 10,
            'status'       => 'new',
        ]);
    }

    public function test_submitting_request_with_room_type_interests(): void
    {
        $roomType = RoomType::factory()->create();

        $this->post('/group-bookings', $this->payload(['room_type_ids' => [$roomType->id]]))->assertRedirect();

        $request = GroupBookingRequest::first();
        $this->assertSame([$roomType->id], $request->room_type_ids);
    }

    public function test_request_fails_when_group_size_below_minimum(): void
    {
        $this->post('/group-bookings', $this->payload(['group_size' => 2]))
            ->assertSessionHasErrors('group_size');

        $this->assertDatabaseCount('group_booking_requests', 0);
    }

    public function test_admin_can_view_group_booking_requests_list(): void
    {
        $admin = $this->makeUser('admin');
        GroupBookingRequest::create($this->payload());

        $this->actingAsAdmin($admin)->get('/admin/group-bookings')->assertOk();
    }

    public function test_admin_can_mark_request_as_contacted(): void
    {
        $admin   = $this->makeUser('admin');
        $request = GroupBookingRequest::create($this->payload());

        $this->actingAsAdmin($admin)
            ->patch("/admin/group-bookings/{$request->id}/mark-contacted")
            ->assertRedirect(route('admin.group-bookings.index'));

        $this->assertSame('contacted', $request->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'group_booking_request.marked_contacted',
            'auditable_id' => $request->id,
            'user_id'      => $admin->id,
        ]);
    }

    public function test_admin_can_delete_request(): void
    {
        $admin   = $this->makeUser('admin');
        $request = GroupBookingRequest::create($this->payload());

        $this->actingAsAdmin($admin)
            ->delete("/admin/group-bookings/{$request->id}")
            ->assertRedirect(route('admin.group-bookings.index'));

        $this->assertDatabaseMissing('group_booking_requests', ['id' => $request->id]);
    }

    public function test_staff_cannot_access_admin_group_bookings(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)
            ->get('/admin/group-bookings')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_access_admin_group_bookings(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->get('/admin/group-bookings')
            ->assertRedirect(route('customer.dashboard'));
    }
}
