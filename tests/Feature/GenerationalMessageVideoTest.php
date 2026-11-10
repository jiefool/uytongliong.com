<?php

namespace Tests\Feature;

use App\Models\GenerationalMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerationalMessageVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function video(string $name = 'message.mp4'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 5, 'video/mp4');
    }

    public function test_a_user_can_add_video_only_message_and_family_viewers_can_play_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('generational-messages.store'), [
            'title' => 'A family story',
            'video' => $this->video(),
        ])->assertRedirect(route('message-to-next-generations'));

        $message = GenerationalMessage::firstOrFail();
        $this->assertNull($message->body);
        Storage::disk('local')->assertExists($message->video_path);
        $this->get(route('message-to-next-generations'))->assertSee('Video message: A family story');
        $this->get(route('generational-messages.video', $message))->assertOk()->assertHeader('Content-Type', 'video/mp4');
        Storage::disk('local')->put($message->video_path, str_repeat('x', 32));
        $this->withHeader('Range', 'bytes=0-9')->get(route('generational-messages.video', $message))->assertStatus(206)->assertHeader('Accept-Ranges', 'bytes');
        $this->withHeader('Range', '');
        auth()->logout();
        $this->get(route('generational-messages.video', $message))->assertRedirect(route('pin.entry'));
        $this->withSession(['tree_pin_access' => 1])->get(route('pin.messages'))->assertSee('Video message: A family story');
        $this->get(route('generational-messages.video', $message))->assertOk();
    }

    public function test_video_is_validated_and_text_or_video_is_required(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('generational-messages.store'), ['title' => 'Empty'])->assertSessionHasErrors('body');
        $this->post(route('generational-messages.store'), ['title' => 'Bad file', 'video' => UploadedFile::fake()->create('bad.pdf', 5, 'application/pdf')])->assertSessionHasErrors('video');
        $this->post(route('generational-messages.store'), ['title' => 'Too large', 'video' => UploadedFile::fake()->create('big.mp4', 10241, 'video/mp4')])->assertSessionHasErrors('video');
        $this->assertDatabaseCount('generational_messages', 0);
    }

    public function test_replacing_removing_and_deleting_video_cleans_up_private_storage(): void
    {
        $this->actingAs(User::factory()->create())->post(route('generational-messages.store'), ['title' => 'Story', 'body' => 'A story', 'video' => $this->video()]);
        $message = GenerationalMessage::firstOrFail();
        $original = $message->video_path;
        $this->put(route('generational-messages.update', $message), ['title' => 'Story', 'body' => 'A story', 'video' => $this->video('replacement.mp4')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($original);
        $replacement = $message->fresh()->video_path;
        Storage::disk('local')->assertExists($replacement);
        $this->put(route('generational-messages.update', $message), ['title' => 'Story', 'body' => 'A story', 'remove_video' => '1'])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($replacement);
        $this->assertNull($message->fresh()->video_path);
        $this->put(route('generational-messages.update', $message), ['title' => 'Story', 'body' => '', 'remove_video' => '1'])->assertSessionHasErrors('body');
        $this->put(route('generational-messages.update', $message), ['title' => 'Story', 'body' => 'A story', 'video' => $this->video('last.mp4')]);
        $last = $message->fresh()->video_path;
        $this->delete(route('generational-messages.destroy', $message))->assertRedirect();
        Storage::disk('local')->assertMissing($last);
    }
}
