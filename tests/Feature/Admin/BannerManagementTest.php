<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test quản lý banner (admin) — CRUD và dọn file ảnh khi thay/xóa banner.
 *
 * Test case ID | Chức năng                                  | Kết quả mong đợi
 * TC-BAN-001   | Admin tạo banner với ảnh upload             | Tạo thành công, file được lưu
 * TC-BAN-002   | Cập nhật banner thay ảnh mới                | Ảnh cũ bị xóa khỏi disk
 * TC-BAN-003   | Xóa banner                                   | Banner và file ảnh bị xóa
 * TC-BAN-004   | Banner dùng link ảnh ngoài không bị đụng file | Xóa banner không lỗi, không đụng file cục bộ
 * TC-BAN-005   | Staff/customer không vào được /admin/banners | Redirect đúng dashboard
 */
class BannerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    public function test_admin_can_create_banner_with_uploaded_image(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('admin');
        $file  = UploadedFile::fake()->image('banner.jpg');

        $this->actingAsAdmin($admin)->post('/admin/banners', [
            'title'      => 'Ưu đãi hè',
            'image_file' => $file,
            'status'     => 'active',
        ])->assertRedirect(route('admin.banners.index'));

        $banner = Banner::where('title', 'Ưu đãi hè')->first();
        $this->assertNotNull($banner);
        Storage::disk('public')->assertExists($banner->image_path);
    }

    public function test_updating_banner_image_deletes_old_file(): void
    {
        Storage::fake('public');
        $admin   = $this->makeUser('admin');
        $oldFile = UploadedFile::fake()->image('old.jpg')->store('banners', 'public');

        $banner = Banner::create([
            'title'      => 'Banner cũ',
            'image_path' => $oldFile,
            'status'     => 'active',
        ]);

        $newFile = UploadedFile::fake()->image('new.jpg');

        $this->actingAsAdmin($admin)->put("/admin/banners/{$banner->id}", [
            'title'      => 'Banner mới',
            'image_file' => $newFile,
            'status'     => 'active',
        ])->assertRedirect(route('admin.banners.index'));

        Storage::disk('public')->assertMissing($oldFile);
        Storage::disk('public')->assertExists($banner->fresh()->image_path);
    }

    public function test_deleting_banner_removes_image_file(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('admin');
        $path  = UploadedFile::fake()->image('banner.jpg')->store('banners', 'public');

        $banner = Banner::create([
            'title'      => 'Banner sẽ xóa',
            'image_path' => $path,
            'status'     => 'active',
        ]);

        $this->actingAsAdmin($admin)
            ->delete("/admin/banners/{$banner->id}")
            ->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_banner_with_external_image_url_does_not_error(): void
    {
        $admin  = $this->makeUser('admin');
        $banner = Banner::create([
            'title'      => 'Banner link ngoài',
            'image_path' => 'https://example.com/banner.jpg',
            'status'     => 'active',
        ]);

        $this->actingAsAdmin($admin)
            ->delete("/admin/banners/{$banner->id}")
            ->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    public function test_staff_cannot_access_admin_banners(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAsAdmin($staff)
            ->get('/admin/banners')
            ->assertRedirect(route('staff.dashboard'));
    }

    public function test_customer_cannot_access_admin_banners(): void
    {
        $customer = $this->makeUser('customer');

        $this->actingAs($customer)
            ->get('/admin/banners')
            ->assertRedirect(route('customer.dashboard'));
    }
}
