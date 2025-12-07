<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FilamentAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_admin_panel_registration_page_is_accessible(): void
    {
        $response = $this->get('/admin/register');

        $response->assertStatus(200);
    }

    public function test_user_can_register(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->post('/admin/register', $userData);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin');
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/admin/logout');

        $this->assertGuest();
        $response->assertRedirect('/admin/login');
    }

    public function test_password_reset_page_is_accessible(): void
    {
        $response = $this->get('/admin/password-reset/request');

        $response->assertStatus(200);
    }

    public function test_admin_panel_is_protected(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_can_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin');

        $response->assertStatus(200);
    }
}
