<?php

namespace Tests\Feature;

use App\Models\GenerationalMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerationalMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_create_update_and_delete_a_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/message-to-next-generations', [
            'title' => 'Remember our roots',
            'from_name' => 'Lola Maria',
            'body' => 'Carry kindness through every generation.',
        ])->assertRedirect('/message-to-next-generations');

        $message = GenerationalMessage::firstOrFail();
        $this->assertSame($user->id, $message->user_id);
        $this->assertSame('Lola Maria', $message->from_name);

        $this->put("/message-to-next-generations/{$message->id}", [
            'title' => 'Remember our family roots',
            'from_name' => 'The Uy-Malmis Clan',
            'body' => 'Carry kindness and courage through every generation.',
        ])->assertRedirect('/message-to-next-generations');

        $this->delete("/message-to-next-generations/{$message->id}")
            ->assertRedirect('/message-to-next-generations');

        $this->assertDatabaseCount('generational_messages', 0);
    }
}
