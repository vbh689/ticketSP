<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'agent@internal.local',
            'username' => 'agent.support',
            'password' => 'password',
            'status' => 'active',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/tickets');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_valid_username(): void
    {
        $user = User::factory()->create([
            'email' => 'agent@internal.local',
            'username' => 'agent.support',
            'password' => 'password',
            'status' => 'active',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect('/tickets');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'agent@internal.local',
            'username' => 'agent.support',
            'password' => 'password',
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'agent.support',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive.agent@internal.local',
            'username' => 'inactive.agent',
            'password' => 'password',
            'status' => 'inactive',
            'is_active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'inactive.agent',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_when_visiting_internal_screen(): void
    {
        $this->get('/tickets')->assertRedirect('/login');
    }
}
