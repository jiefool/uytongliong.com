<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_manage_users_and_secretariat_cannot_access_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretariat = User::factory()->create(['role' => 'secretariat']);

        $this->actingAs($secretariat)->get('/users')->assertForbidden();

        $this->actingAs($admin)->post('/users', [
            'name' => 'Archive Keeper',
            'email' => 'keeper@example.com',
            'role' => 'secretariat',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/users');

        $this->assertDatabaseHas('users', ['email' => 'keeper@example.com', 'role' => 'secretariat']);
    }
}
