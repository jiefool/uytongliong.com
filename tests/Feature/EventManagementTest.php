<?php

namespace Tests\Feature;

use App\Models\ClanEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 9)->startOfDay());
    }

    private function event(array $attributes = []): ClanEvent
    {
        return ClanEvent::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'title' => 'Family Reunion',
            'event_date' => '2026-12-20',
        ], $attributes));
    }

    public function test_drafts_are_private_and_only_organizers_or_admins_can_manage_events(): void
    {
        $event = $this->event(['status' => 'draft']);
        $this->get('/events?period=all')->assertDontSee('Family Reunion');
        $this->get(route('events.show', $event))->assertNotFound();
        $this->get(route('events.index'))->assertRedirect(route('login'));
        $this->post(route('events.rsvp', $event), ['response' => 'going'])->assertRedirect(route('login'));

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)->get(route('events.show', $event))->assertNotFound();
        $this->get(route('events.index'))->assertOk()->assertDontSee('Family Reunion');
        $this->get(route('events.edit', $event))->assertForbidden();
        $this->put(route('events.update', $event), ['title' => 'Changed', 'event_date' => '2026-12-20'])->assertForbidden();
        $this->delete(route('events.destroy', $event))->assertForbidden();

        $this->actingAs($event->creator)->get(route('events.show', $event))->assertOk()->assertSee('private draft');
        $this->get(route('events.edit', $event))->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('events.index'))->assertOk()->assertSee('Family Reunion');
        $this->put(route('events.update', $event), ['title' => 'Published reunion', 'event_date' => '2026-12-20', 'status' => 'published'])->assertRedirect();
        $this->assertSame('published', $event->fresh()->status);
        $this->assertSame('family-reunion', $event->fresh()->slug);
    }

    public function test_schedule_and_status_validation_and_all_day_events(): void
    {
        $this->actingAs(User::factory()->create());
        $payload = ['title' => 'Gathering', 'event_date' => '2026-12-20'];
        $this->post('/events', $payload + ['start_time' => '15:00', 'end_time' => '14:00'])->assertSessionHasErrors('end_time');
        $this->post('/events', $payload + ['end_time' => '14:00'])->assertSessionHasErrors('start_time');
        $this->post('/events', $payload + ['timezone' => 'invalid', 'status' => 'invalid'])->assertSessionHasErrors(['timezone', 'status']);
        $this->post('/events', $payload + ['start_time' => '14:00', 'end_time' => '15:00', 'timezone' => 'Asia/Manila', 'status' => 'draft'])->assertSessionHasNoErrors();
        $event = ClanEvent::firstOrFail();
        $this->get(route('events.show', $event))->assertOk()->assertSee('14:00')->assertSee('Asia/Manila');
        $this->put(route('events.update', $event), $payload + ['start_time' => '', 'end_time' => '', 'status' => 'published'])->assertSessionHasNoErrors();
        $this->assertNull($event->fresh()->start_time);
        $this->get(route('events.show', $event))->assertSee('All day');
    }

    public function test_public_calendar_filters_search_and_pagination_preserve_visibility(): void
    {
        $this->event(['title' => 'Upcoming Manila Reunion', 'location' => 'Manila']);
        $this->event(['title' => 'Past Manila Reunion', 'event_date' => '2026-10-01']);
        $this->event(['title' => 'Secret Manila Reunion', 'status' => 'draft']);
        $cancelled = $this->event(['title' => 'Cancelled Cebu Reunion', 'status' => 'cancelled']);
        $this->get('/events')->assertSee('Upcoming Manila Reunion')->assertDontSee('Past Manila Reunion')->assertDontSee('Secret Manila Reunion');
        $this->get('/events?period=past')->assertSee('Past Manila Reunion')->assertDontSee('Upcoming Manila Reunion');
        $this->get('/events?q=Manila&period=all')->assertSee('Upcoming Manila Reunion')->assertSee('Past Manila Reunion')->assertDontSee('Cancelled Cebu Reunion')->assertDontSee('Secret Manila Reunion');
        $this->get('/events?status=cancelled')->assertSee('Cancelled Cebu Reunion')->assertDontSee('Upcoming Manila Reunion');
        $this->get(route('events.show', $cancelled))->assertOk()->assertSee('This event has been cancelled');
        for ($i = 0; $i < 13; $i++) {
            $this->event(['title' => 'Paginated gathering '.$i]);
        }
        $this->get('/events?q=Paginated&period=all')->assertOk()->assertSee('page=2')->assertSee('q=Paginated');
        $this->get('/events?q=Paginated&period=all&page=2')->assertOk()->assertSee('Paginated gathering 12');
    }

    public function test_member_can_change_one_rsvp_and_only_managers_see_attendee_names(): void
    {
        $event = $this->event();
        $member = User::factory()->create(['name' => 'Private Attendee Name']);
        $this->actingAs($member)->post(route('events.rsvp', $event), ['response' => 'going'])->assertRedirect(route('events.show', $event));
        $this->post(route('events.rsvp', $event), ['response' => 'maybe', 'user_id' => $event->user_id])->assertRedirect();
        $this->assertDatabaseCount('event_rsvps', 1);
        $this->assertDatabaseHas('event_rsvps', ['user_id' => $member->id, 'response' => 'maybe']);
        $this->post(route('events.rsvp', $event), ['response' => 'invalid'])->assertSessionHasErrors('response');
        $this->get(route('events.show', $event))->assertSee('Your response:')->assertDontSee('Private Attendee Name');
        $this->actingAs($event->creator)->get(route('events.show', $event))->assertSee('Private Attendee Name')->assertSee('1 maybe');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('events.show', $event))->assertSee('Private Attendee Name');
        $this->actingAs($event->creator)->delete(route('events.destroy', $event))->assertRedirect();
        $this->assertDatabaseCount('event_rsvps', 0);
    }

    public function test_rsvps_close_for_drafts_cancelled_and_finished_events_in_event_timezone(): void
    {
        $this->actingAs(User::factory()->create());
        $draft = $this->event(['status' => 'draft']);
        $this->post(route('events.rsvp', $draft), ['response' => 'going'])->assertNotFound();
        foreach ([['status' => 'cancelled'], ['event_date' => '2026-11-08'], ['event_date' => '2026-11-09', 'start_time' => '07:00', 'timezone' => 'Asia/Manila']] as $attributes) {
            $event = $this->event($attributes);
            $this->post(route('events.rsvp', $event), ['response' => 'going'])->assertUnprocessable();
        }
        $allDay = $this->event(['event_date' => '2026-11-09', 'timezone' => 'Asia/Manila']);
        $this->post(route('events.rsvp', $allDay), ['response' => 'going'])->assertRedirect();
        $this->assertDatabaseCount('event_rsvps', 1);
    }

    public function test_replacing_removing_and_deleting_posters_cleans_up_storage(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $payload = ['title' => 'Posters', 'event_date' => '2026-12-20'];
        $this->post('/events', $payload + ['poster_image' => UploadedFile::fake()->image('first.jpg')]);
        $event = ClanEvent::firstOrFail();
        $original = $event->poster_image_path;
        $this->put(route('events.update', $event), $payload + ['poster_image' => UploadedFile::fake()->image('second.jpg')])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($original);
        $second = $event->fresh()->poster_image_path;
        Storage::disk('public')->assertExists($second);
        $this->put(route('events.update', $event), $payload + ['remove_poster' => '1'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($event->fresh()->poster_image_path);
        $this->put(route('events.update', $event), $payload + ['poster_image' => UploadedFile::fake()->image('third.jpg')]);
        $third = $event->fresh()->poster_image_path;
        $this->delete(route('events.destroy', $event));
        Storage::disk('public')->assertMissing($third);
    }
}
