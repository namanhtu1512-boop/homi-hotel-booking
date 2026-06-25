<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test case logout qua route Blade /logout.
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
}
