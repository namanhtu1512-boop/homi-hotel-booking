<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test quản lý tin tức (admin CRUD + trang public) — tính năng ngoài kế
 * hoạch gốc, trước đây chưa có test nào che phủ.
 *
 * Test case ID | Chức năng                                   | Kết quả mong đợi
 * TC-NEWS-001  | Trang public danh sách tin đã published       | 200, không thấy tin draft
 * TC-NEWS-002  | Trang public chi tiết tin theo slug            | 200
 * TC-NEWS-003  | Tin draft không xem được qua trang public       | 404
 * TC-NEWS-004  | Admin xem danh sách tin (admin)                | 200
 * TC-NEWS-005  | Staff/customer không vào được /admin/news       | Redirect đúng dashboard
 * TC-NEWS-006  | Admin tạo tin mới, slug tự sinh từ tiêu đề      | Tạo thành công, có slug
 * TC-NEWS-007  | Tạo tin trùng tiêu đề → slug tự thêm số         | Slug thứ 2 có hậu tố -2
 * TC-NEWS-008  | Admin cập nhật tin                              | Nội dung thay đổi đúng
 * TC-NEWS-009  | Admin xóa tin                                   | Bị xóa khỏi DB
 * TC-NEWS-010  | Tạo tin thiếu tiêu đề                           | Lỗi validation
 */
class NewsManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function newsPayload(array $overrides = []): array
    {
        return array_merge([
            'title'   => 'Khai trương hồ bơi mới',
            'excerpt' => 'Hồ bơi vô cực tầng thượng chính thức mở cửa.',
            'content' => 'Nội dung chi tiết về hồ bơi mới...',
            'status'  => 'published',
        ], $overrides);
    }

    public function test_public_can_view_published_news_list(): void
    {
        News::create($this->newsPayload(['slug' => 'tin-published', 'status' => 'published']));
        News::create($this->newsPayload(['title' => 'Tin nháp', 'slug' => 'tin-draft', 'status' => 'draft']));

        $response = $this->get('/news');

        $response->assertOk();
        $response->assertSee('Khai trương hồ bơi mới');
        $response->assertDontSee('Tin nháp');
    }

    public function test_public_can_view_news_detail_by_slug(): void
    {
        News::create($this->newsPayload(['slug' => 'khai-truong-ho-boi', 'status' => 'published']));

        $this->get('/news/khai-truong-ho-boi')->assertOk();
    }

    public function test_draft_news_returns_404_on_public_detail_page(): void
    {
        News::create($this->newsPayload(['slug' => 'tin-nhap-nhap', 'status' => 'draft']));

        $this->get('/news/tin-nhap-nhap')->assertNotFound();
    }

    public function test_admin_can_view_news_list(): void
    {
        $admin = $this->makeUser('admin');
        News::create($this->newsPayload(['slug' => 'tin-1']));

        $this->actingAsAdmin($admin)->get('/admin/news')->assertOk();
    }

    public function test_staff_cannot_access_admin_news(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)
            ->get('/admin/news')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_access_admin_news(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->get('/admin/news')
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_admin_can_create_news_with_auto_generated_slug(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)
            ->post('/admin/news', $this->newsPayload())
            ->assertRedirect(route('admin.news.index'));

        $this->assertDatabaseHas('news', [
            'title' => 'Khai trương hồ bơi mới',
            'slug'  => 'khai-truong-ho-boi-moi',
        ]);
    }

    public function test_duplicate_title_gets_unique_slug_suffix(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)->post('/admin/news', $this->newsPayload());
        $this->actingAsAdmin($admin)->post('/admin/news', $this->newsPayload());

        $this->assertDatabaseHas('news', ['slug' => 'khai-truong-ho-boi-moi']);
        $this->assertDatabaseHas('news', ['slug' => 'khai-truong-ho-boi-moi-2']);
    }

    public function test_admin_can_update_news(): void
    {
        $admin   = $this->makeUser('admin');
        $article = News::create($this->newsPayload(['slug' => 'tin-cu']));

        $this->actingAsAdmin($admin)
            ->put("/admin/news/{$article->id}", $this->newsPayload(['title' => 'Tiêu đề đã sửa']))
            ->assertRedirect(route('admin.news.index'));

        $this->assertSame('Tiêu đề đã sửa', $article->fresh()->title);
    }

    public function test_admin_can_delete_news(): void
    {
        $admin   = $this->makeUser('admin');
        $article = News::create($this->newsPayload(['slug' => 'tin-se-xoa']));

        $this->actingAsAdmin($admin)
            ->delete("/admin/news/{$article->id}")
            ->assertRedirect(route('admin.news.index'));

        $this->assertDatabaseMissing('news', ['id' => $article->id]);
    }

    public function test_create_news_fails_without_title(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsAdmin($admin)
            ->post('/admin/news', $this->newsPayload(['title' => '']))
            ->assertSessionHasErrors('title');
    }
}
