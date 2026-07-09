<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Test case cho /customer/profile: xem, cập nhật thông tin, đổi mật khẩu,
 * đổi email — và logout qua route Blade /logout.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_success(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_customer_can_view_profile_page(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get(route('customer.profile.show'))
            ->assertOk()
            ->assertViewIs('customer.profile');
    }

    public function test_customer_can_update_profile(): void
    {
        $user = User::factory()->customer()->create(['name' => 'Tên Cũ']);

        $this->actingAs($user)
            ->post(route('customer.profile.update'), [
                'name'    => 'Tên Mới',
                'phone'   => '0912345678',
                'address' => '123 Đường ABC',
            ])
            ->assertRedirect(route('customer.profile.show'));

        $this->assertDatabaseHas('users', [
            'id'      => $user->id,
            'name'    => 'Tên Mới',
            'phone'   => '0912345678',
            'address' => '123 Đường ABC',
        ]);
    }

    public function test_update_profile_fails_when_name_missing(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->post(route('customer.profile.update'), ['phone' => '0912345678'])
            ->assertSessionHasErrors(['name']);
    }

    public function test_customer_can_change_password(): void
    {
        $user = User::factory()->customer()->create(['password' => Hash::make('mat-khau-cu')]);

        $this->actingAs($user)
            ->post(route('customer.profile.password'), [
                'current_password'    => 'mat-khau-cu',
                'password'             => 'mat-khau-moi',
                'password_confirmation' => 'mat-khau-moi',
            ])
            ->assertRedirect(route('customer.profile.show'));

        $this->assertTrue(Hash::check('mat-khau-moi', $user->fresh()->password));
    }

    public function test_change_password_fails_when_current_password_is_wrong(): void
    {
        $user = User::factory()->customer()->create(['password' => Hash::make('mat-khau-cu')]);

        $this->actingAs($user)
            ->post(route('customer.profile.password'), [
                'current_password'     => 'sai-mat-khau',
                'password'              => 'mat-khau-moi',
                'password_confirmation' => 'mat-khau-moi',
            ])
            ->assertSessionHasErrors(['current_password']);

        $this->assertTrue(Hash::check('mat-khau-cu', $user->fresh()->password));
    }

    public function test_change_password_fails_when_confirmation_does_not_match(): void
    {
        $user = User::factory()->customer()->create(['password' => Hash::make('mat-khau-cu')]);

        $this->actingAs($user)
            ->post(route('customer.profile.password'), [
                'current_password'     => 'mat-khau-cu',
                'password'              => 'mat-khau-moi',
                'password_confirmation' => 'khong-khop',
            ])
            ->assertSessionHasErrors(['password']);
    }

    public function test_customer_can_change_email(): void
    {
        $user = User::factory()->customer()->create([
            'email'    => 'cu@example.com',
            'password' => Hash::make('mat-khau'),
        ]);

        $this->actingAs($user)
            ->post(route('customer.profile.email'), [
                'email'            => 'moi@example.com',
                'current_password' => 'mat-khau',
            ])
            ->assertRedirect(route('customer.profile.show'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'moi@example.com']);
    }

    public function test_change_email_fails_when_already_taken(): void
    {
        User::factory()->customer()->create(['email' => 'daton@example.com']);
        $user = User::factory()->customer()->create(['password' => Hash::make('mat-khau')]);

        $this->actingAs($user)
            ->post(route('customer.profile.email'), [
                'email'            => 'daton@example.com',
                'current_password' => 'mat-khau',
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_change_email_fails_when_current_password_is_wrong(): void
    {
        $user = User::factory()->customer()->create(['password' => Hash::make('mat-khau')]);

        $this->actingAs($user)
            ->post(route('customer.profile.email'), [
                'email'            => 'moi@example.com',
                'current_password' => 'sai-mat-khau',
            ])
            ->assertSessionHasErrors(['current_password']);
    }
}
