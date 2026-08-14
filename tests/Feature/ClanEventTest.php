<?php

namespace Tests\Feature;

use App\Models\ClanEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClanEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_create_update_and_delete_clan_events(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/events', [
            'title' => 'Family Reunion',
            'event_date' => '2026-12-20',
            'location' => 'Quezon City',
            'description' => 'Our annual gathering.',
        ])->assertRedirect('/events/manage');

        $event = ClanEvent::firstOrFail();
        $this->assertSame($user->id, $event->user_id);
        $this->assertSame('family-reunion', $event->slug);

        $this->put(route('events.update', $event), [
            'title' => 'Uy-Malmis Family Reunion',
            'event_date' => '2026-12-21',
        ])->assertRedirect('/events/manage');

        $event->refresh();
        $this->delete(route('events.destroy', $event))->assertRedirect('/events/manage');
        $this->assertDatabaseCount('clan_events', 0);
    }

    public function test_an_event_poster_can_be_uploaded(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create())->post('/events', [
            'title' => 'Family Reunion',
            'event_date' => '2026-12-20',
            'poster_image' => UploadedFile::fake()->image('reunion.jpg'),
        ])->assertRedirect('/events/manage');

        $event = ClanEvent::firstOrFail();
        Storage::disk('public')->assertExists($event->poster_image_path);
    }

    public function test_events_and_event_details_are_publicly_viewable(): void
    {
        $event = ClanEvent::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Family Reunion',
            'event_date' => '2026-12-20',
            'location' => 'Quezon City',
        ]);

        $this->get('/events')->assertOk()->assertSee('Family Reunion');
        $this->get(route('events.show', $event))->assertOk()->assertSee('Quezon City');
    }
}
