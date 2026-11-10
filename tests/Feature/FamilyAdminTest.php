<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_family_admin_and_new_registrations_use_the_restricted_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Family Keeper',
            'email' => 'keeper@example.com',
            'role' => 'family_admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/users');

        $this->assertDatabaseHas('users', ['email' => 'keeper@example.com', 'role' => 'family_admin']);
        $this->get('/users')->assertOk()->assertSee('Family Admin')->assertSee('value="family_admin"', false);

        auth()->logout();
        $this->post('/register', [
            'name' => 'New Relative',
            'email' => 'relative@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/family-tree');

        $this->assertDatabaseHas('users', ['email' => 'relative@example.com', 'role' => 'family_admin']);
    }

    public function test_family_admin_can_add_and_edit_only_members_they_created(): void
    {
        $familyAdmin = User::factory()->create(['role' => 'family_admin']);
        $otherAdmin = User::factory()->create(['role' => 'family_admin']);
        $existingParent = FamilyMember::factory()->create(['first_name' => 'Existing']);
        $otherMember = FamilyMember::factory()->create(['created_by_user_id' => $otherAdmin->id]);

        $this->actingAs($familyAdmin);
        $created = $this->postJson('/members', [
            'first_name' => 'Elena',
            'parent_id' => $existingParent->id,
            'created_by_user_id' => $otherAdmin->id,
        ])->assertCreated();

        $memberId = $created->json('id');
        $this->assertDatabaseHas('family_members', ['id' => $memberId, 'parent_id' => $existingParent->id, 'created_by_user_id' => $familyAdmin->id]);
        $this->putJson("/members/{$memberId}", ['first_name' => 'Elena Marie'])->assertOk();
        $this->putJson("/members/{$existingParent->id}", ['first_name' => 'Changed'])->assertForbidden();
        $this->putJson("/members/{$otherMember->id}", ['first_name' => 'Changed'])->assertForbidden();
        $this->deleteJson("/members/{$memberId}")->assertForbidden();

        $list = $this->getJson('/members')->assertOk();
        $byId = collect($list->json())->keyBy('id');
        $this->assertTrue($byId[$memberId]['can_edit']);
        $this->assertFalse($byId[$existingParent->id]['can_edit']);
        $this->assertFalse($byId[$otherMember->id]['can_edit']);
        $this->assertDatabaseHas('family_members', ['id' => $memberId, 'first_name' => 'Elena Marie']);
        $this->assertDatabaseHas('family_members', ['id' => $existingParent->id, 'first_name' => 'Existing']);
    }

    public function test_family_admin_cannot_change_another_creators_spouse_record(): void
    {
        $familyAdmin = User::factory()->create(['role' => 'family_admin']);
        $owned = FamilyMember::factory()->create(['created_by_user_id' => $familyAdmin->id]);
        $ownedPartner = FamilyMember::factory()->create(['created_by_user_id' => $familyAdmin->id]);
        $unowned = FamilyMember::factory()->create();

        $this->actingAs($familyAdmin);
        $this->postJson('/members', ['first_name' => 'Outside Spouse', 'spouse_id' => $unowned->id])->assertForbidden();
        $this->putJson("/members/{$owned->id}", ['first_name' => $owned->first_name, 'spouse_id' => $unowned->id])->assertForbidden();
        $this->assertNull($unowned->fresh()->spouse_id);

        $this->putJson("/members/{$owned->id}", ['first_name' => $owned->first_name, 'spouse_id' => $ownedPartner->id])->assertOk();
        $this->assertSame($owned->id, $ownedPartner->fresh()->spouse_id);
        $this->putJson("/members/{$owned->id}", ['first_name' => $owned->first_name, 'spouse_id' => null])->assertOk();
        $this->assertNull($ownedPartner->fresh()->spouse_id);
    }

    public function test_family_admin_cannot_manage_users_or_other_site_content(): void
    {
        $familyAdmin = User::factory()->create(['role' => 'family_admin']);
        $member = FamilyMember::factory()->create(['created_by_user_id' => $familyAdmin->id]);

        $this->actingAs($familyAdmin);
        $this->get('/family-tree')->assertOk()->assertSee('name="is-family-admin" content="true"', false);
        $this->get('/users')->assertForbidden();
        $this->get('/access-pins')->assertForbidden();
        $this->post('/access-pins', ['label' => 'Visitor'])->assertForbidden();
        $this->get('/events/manage')->assertForbidden();
        $this->post('/events', ['title' => 'Meeting'])->assertForbidden();
        $this->get('/newsletters/manage')->assertForbidden();
        $this->post('/newsletters', ['title' => 'Issue'])->assertForbidden();
        $this->get('/message-to-next-generations')->assertForbidden();
        $this->post('/message-to-next-generations', ['title' => 'Letter'])->assertForbidden();
        $this->deleteJson("/members/{$member->id}")->assertForbidden();
    }
}
