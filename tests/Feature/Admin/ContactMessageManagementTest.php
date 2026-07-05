<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test quản lý liên hệ (admin) — đánh dấu đã đọc, xóa, và audit log tương ứng.
 *
 * Test case ID | Chức năng                                  | Kết quả mong đợi
 * TC-CTM-001   | Khách gửi liên hệ công khai                 | Tạo bản ghi contact_messages
 * TC-CTM-002   | Admin xem danh sách liên hệ                 | 200
 * TC-CTM-003   | Admin đánh dấu đã đọc                       | status = read, có audit log
 * TC-CTM-004   | Admin xóa liên hệ                           | Bị xóa khỏi DB, có audit log
 * TC-CTM-005   | Staff/customer không vào được /admin/contact-messages | Redirect đúng dashboard
 */
class ContactMessageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    public function test_guest_can_submit_contact_message(): void
    {
        $this->post('/contact', [
            'name'    => 'Nguyen Van A',
            'email'   => 'a@example.com',
            'message' => 'Cho hoi phong con trong khong?',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', ['email' => 'a@example.com']);
    }

    public function test_admin_can_view_contact_messages_list(): void
    {
        $admin = $this->makeUser('admin');
        ContactMessage::create([
            'name' => 'Khách A', 'email' => 'a@example.com', 'message' => 'Hỏi giá phòng', 'status' => 'unread',
        ]);

        $this->actingAsAdmin($admin)->get('/admin/contact-messages')->assertOk();
    }

    public function test_admin_can_mark_message_as_read_and_it_is_audit_logged(): void
    {
        $admin   = $this->makeUser('admin');
        $message = ContactMessage::create([
            'name' => 'Khách A', 'email' => 'a@example.com', 'message' => 'Hỏi giá phòng', 'status' => 'unread',
        ]);

        $this->actingAsAdmin($admin)
            ->patch("/admin/contact-messages/{$message->id}/read")
            ->assertRedirect(route('admin.contact-messages.index'));

        $this->assertSame('read', $message->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'contact_message.marked_read',
            'auditable_id'   => $message->id,
            'user_id'        => $admin->id,
        ]);
    }

    public function test_admin_can_delete_message_and_it_is_audit_logged(): void
    {
        $admin   = $this->makeUser('admin');
        $message = ContactMessage::create([
            'name' => 'Khách A', 'email' => 'a@example.com', 'message' => 'Hỏi giá phòng', 'status' => 'unread',
        ]);

        $this->actingAsAdmin($admin)
            ->delete("/admin/contact-messages/{$message->id}")
            ->assertRedirect(route('admin.contact-messages.index'));

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action'  => 'contact_message.deleted',
            'user_id' => $admin->id,
        ]);
    }

    public function test_staff_cannot_access_admin_contact_messages(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)
            ->get('/admin/contact-messages')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_access_admin_contact_messages(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->get('/admin/contact-messages')
            ->assertRedirect(route('customer.dashboard'));
    }
}
