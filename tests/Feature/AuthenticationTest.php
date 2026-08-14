<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_the_homepage_but_the_tree_requires_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Uy-Malmis Clan');
        $this->get('/family-tree')->assertRedirect('/login');
    }

    public function test_users_can_register_and_access_the_tree(): void
    {
        $this->post('/register', [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/family-tree');

        $this->assertAuthenticated();
    }

    public function test_users_can_log_in(): void
    {
        User::factory()->create(['email' => 'maria@example.com', 'password' => 'password123']);

        $this->post('/login', ['email' => 'maria@example.com', 'password' => 'password123'])
            ->assertRedirect('/family-tree');

        $this->assertAuthenticated();
    }
}
