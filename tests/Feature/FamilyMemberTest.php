<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FamilyMemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_a_member_can_be_added_to_a_parent_branch(): void
    {
        $parent = FamilyMember::factory()->create();

        $this->postJson('/members', [
            'first_name' => 'Elena',
            'last_name' => 'Santos',
            'parent_id' => $parent->id,
            'gender' => 'female',
            'life_status' => 'alive',
            'address' => 'Quezon City, Metro Manila',
            'contact_number' => '+63 917 555 0101',
            'email' => 'elena@example.com',
            'google_maps_url' => 'https://maps.google.com/?q=Quezon+City',
            'facebook_url' => 'https://facebook.com/elena.santos',
            'instagram_url' => 'https://instagram.com/elena.santos',
            'tiktok_url' => 'https://tiktok.com/@elena.santos',
            'x_url' => 'https://x.com/elena_santos',
        ])->assertCreated()->assertJsonPath('first_name', 'Elena');

        $this->assertDatabaseHas('family_members', ['first_name' => 'Elena', 'parent_id' => $parent->id, 'address' => 'Quezon City, Metro Manila', 'email' => 'elena@example.com', 'life_status' => 'alive']);
    }

    public function test_the_member_list_is_available_to_the_tree(): void
    {
        FamilyMember::factory()->count(2)->create();

        $this->getJson('/members')->assertOk()->assertJsonCount(2);
    }

    public function test_adding_a_member_with_a_spouse_creates_a_reciprocal_relationship(): void
    {
        $spouse = FamilyMember::factory()->create();

        $response = $this->postJson('/members', [
            'first_name' => 'Miguel',
            'spouse_id' => $spouse->id,
        ])->assertCreated();

        $this->assertDatabaseHas('family_members', ['id' => $spouse->id, 'spouse_id' => $response->json('id')]);
        $this->assertDatabaseHas('family_members', ['id' => $response->json('id'), 'spouse_id' => $spouse->id]);
    }

    public function test_a_member_can_be_updated(): void
    {
        $member = FamilyMember::factory()->create(['first_name' => 'Ana']);

        $this->putJson("/members/{$member->id}", [
            'first_name' => 'Anastacia',
            'last_name' => 'Reyes',
            'address' => 'Makati City',
        ])->assertOk()->assertJsonPath('first_name', 'Anastacia');

        $this->assertDatabaseHas('family_members', ['id' => $member->id, 'first_name' => 'Anastacia', 'address' => 'Makati City']);
    }

    public function test_a_member_can_have_two_distinct_parents(): void
    {
        $firstParent = FamilyMember::factory()->create();
        $secondParent = FamilyMember::factory()->create();

        $this->postJson('/members', [
            'first_name' => 'Lucas',
            'parent_id' => $firstParent->id,
            'secondary_parent_id' => $secondParent->id,
        ])->assertCreated();

        $this->assertDatabaseHas('family_members', [
            'first_name' => 'Lucas',
            'parent_id' => $firstParent->id,
            'secondary_parent_id' => $secondParent->id,
        ]);
    }

    public function test_a_profile_image_can_be_uploaded_for_a_member(): void
    {
        Storage::fake('public');

        $this->post('/members', [
            'first_name' => 'Lina',
            'profile_image' => UploadedFile::fake()->image('lina.jpg'),
        ])->assertCreated();

        $member = FamilyMember::where('first_name', 'Lina')->firstOrFail();
        $this->assertNotNull($member->profile_image_path);
        Storage::disk('public')->assertExists($member->profile_image_path);
    }

    public function test_deleting_a_member_clears_related_spouse_and_parent_links(): void
    {
        $member = FamilyMember::factory()->create();
        $spouse = FamilyMember::factory()->create(['spouse_id' => $member->id]);
        $child = FamilyMember::factory()->create(['parent_id' => $member->id, 'secondary_parent_id' => $spouse->id]);

        $this->deleteJson("/members/{$member->id}")->assertOk();

        $this->assertDatabaseMissing('family_members', ['id' => $member->id]);
        $this->assertDatabaseHas('family_members', ['id' => $spouse->id, 'spouse_id' => null]);
        $this->assertDatabaseHas('family_members', ['id' => $child->id, 'parent_id' => null, 'secondary_parent_id' => $spouse->id]);
    }
}
