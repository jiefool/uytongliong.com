<?php

namespace Tests\Feature;

use App\Models\TreeAccessPin;
use App\Models\User;
use App\Models\GenerationalMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TreePinAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_generate_pins_and_guests_can_use_them_for_view_access(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/access-pins', ['label' => 'For cousins'])->assertRedirect('/access-pins');
        $this->assertDatabaseCount('tree_access_pins', 1);

        $this->assertSame('For cousins', TreeAccessPin::firstOrFail()->label);
        $this->assertNotNull(TreeAccessPin::firstOrFail()->pin_encrypted);

        TreeAccessPin::query()->first()->update(['pin_hash' => Hash::make('123456')]);
        GenerationalMessage::create(['user_id' => $user->id, 'title' => 'Our family promise', 'body' => 'Keep our stories close.']);

        $this->post('/view', ['pin' => '123456'])->assertRedirect('/view/family-tree');
        $this->get('/view/family-tree')->assertOk();
        $this->get('/view/tree')->assertOk()->assertSee('Tree View');
        $this->get('/members')->assertOk();
        $this->get('/view/messages')->assertOk()->assertSee('Our family promise')->assertDontSee('Add to the archive');
    }

    public function test_tree_view_requires_sign_in_or_pin_access(): void
    {
        $this->get('/family-tree/tree')->assertRedirect('/login');
        $this->get('/view/tree')->assertRedirect('/view');

        $this->actingAs(User::factory()->create())->get('/family-tree/tree')->assertOk()->assertSee('tree-view-app');
    }
}
