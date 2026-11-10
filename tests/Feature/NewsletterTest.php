<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $attributes = []): array
    {
        return array_merge([
            'title' => 'Family News',
            'issue_date' => '2026-11-09',
            'summary' => 'Stories from our family.',
            'body' => "Welcome to our newsletter.\n\nCelebrating another year together.",
            'status' => 'draft',
        ], $attributes);
    }

    private function newsletter(array $attributes = [], ?User $author = null): Newsletter
    {
        return ($author ?? User::factory()->create())->newsletters()->create($this->payload($attributes));
    }

    public function test_author_can_create_publish_edit_archive_republish_and_delete(): void
    {
        $author = User::factory()->create();
        $this->actingAs($author)->get(route('newsletters.create'))->assertOk()->assertSee('Publication settings');
        $this->post(route('newsletters.store'), $this->payload(['user_id' => 999, 'published_at' => now(), 'slug' => 'fake']))->assertSessionHasNoErrors();
        $newsletter = Newsletter::firstOrFail();
        $slug = $newsletter->slug;
        $this->assertSame($author->id, $newsletter->user_id);
        $this->assertNull($newsletter->published_at);
        $this->assertNotSame('fake', $slug);
        $this->get(route('newsletters.edit', $newsletter))->assertOk()->assertSee('Welcome to our newsletter.');
        $this->put(route('newsletters.update', $newsletter), $this->payload(['status' => 'published']))->assertRedirect(route('newsletters.edit', $newsletter));
        $publishedAt = $newsletter->fresh()->published_at;
        $this->assertNotNull($publishedAt);
        $this->travel(1)->days();
        $this->put(route('newsletters.update', $newsletter), $this->payload(['title' => 'New title', 'status' => 'archived']))->assertSessionHasNoErrors();
        $this->assertSame($slug, $newsletter->fresh()->slug);
        $this->get(route('newsletters.index').'?status=archived')->assertSee('New title');
        $this->get(route('newsletters.show', $newsletter))->assertOk()->assertSee('archived issue');
        $this->put(route('newsletters.update', $newsletter), $this->payload(['status' => 'published']));
        $this->assertTrue($publishedAt->equalTo($newsletter->fresh()->published_at));
        $this->delete(route('newsletters.destroy', $newsletter))->assertRedirect(route('newsletters.index'));
        $this->assertDatabaseCount('newsletters', 0);
    }

    public function test_guests_only_see_published_issues_and_cannot_write(): void
    {
        $draft = $this->newsletter(['title' => 'Private draft']);
        $archived = $this->newsletter(['title' => 'Hidden archive', 'status' => 'archived']);
        $published = $this->newsletter(['title' => 'Public news', 'status' => 'published']);
        $this->get(route('newsletters.public'))->assertOk()->assertSee('Public news')->assertDontSee('Private draft')->assertDontSee('Hidden archive');
        $this->get(route('newsletters.show', $draft))->assertNotFound();
        $this->get(route('newsletters.show', $archived))->assertNotFound();
        $this->get(route('newsletters.show', $published))->assertOk()->assertSee('Celebrating another year together.');
        foreach (['newsletters.index', 'newsletters.create'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $this->get(route('newsletters.edit', $published))->assertRedirect(route('login'));
        $this->post(route('newsletters.store'), $this->payload())->assertRedirect(route('login'));
        $this->put(route('newsletters.update', $published), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('newsletters.destroy', $published))->assertRedirect(route('login'));
    }

    public function test_only_author_and_admin_can_manage_or_preview_private_issues(): void
    {
        $newsletter = $this->newsletter(['title' => 'Other authors private issue']);
        $other = User::factory()->create();
        $mine = $this->newsletter(['title' => 'My newsletter'], $other);
        $this->actingAs($other)->get(route('newsletters.index'))->assertOk()->assertSee($mine->title)->assertDontSee($newsletter->title)->assertViewHas('stats', fn ($stats) => $stats['total'] === 1);
        $this->get(route('newsletters.show', $newsletter))->assertNotFound();
        $this->get(route('newsletters.edit', $newsletter))->assertForbidden();
        $this->put(route('newsletters.update', $newsletter), $this->payload())->assertForbidden();
        $this->delete(route('newsletters.destroy', $newsletter))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('newsletters.index'))->assertSee($newsletter->title)->assertSee($mine->title);
        $this->get(route('newsletters.show', $newsletter))->assertOk();
        $this->get(route('newsletters.edit', $newsletter))->assertOk();
        $this->put(route('newsletters.update', $newsletter), $this->payload(['status' => 'published']))->assertRedirect();
        $this->assertSame('published', $newsletter->fresh()->status);
        $this->assertSame($newsletter->user_id, $newsletter->fresh()->user_id);
        $this->delete(route('newsletters.destroy', $newsletter))->assertRedirect();
    }

    public function test_search_filters_pagination_and_sorting_do_not_leak_private_content(): void
    {
        $author = User::factory()->create();
        $this->newsletter(['title' => 'Searchable secret', 'summary' => 'Searchable'], $author);
        $this->newsletter(['title' => 'Archived Searchable', 'status' => 'archived'], $author);
        for ($i = 0; $i < 13; $i++) {
            $this->newsletter(['title' => 'Issue '.$i, 'summary' => 'Searchable', 'status' => 'published', 'issue_date' => sprintf('2026-11-%02d', $i + 1)], $author);
        }
        $this->get('/newsletters?q=Searchable')->assertOk()->assertDontSee('Searchable secret')->assertDontSee('Archived Searchable')->assertSee('page=2')->assertSee('q=Searchable')->assertSeeInOrder(['Issue 12', 'Issue 11']);
        $this->get('/newsletters?q=Searchable&page=2')->assertSee('Issue 0');
        $this->get('/newsletters?q=NoMatch')->assertSee('No newsletters found');
        $this->actingAs($author)->get('/newsletters/manage?status=draft')->assertSee('Searchable secret')->assertDontSee('Archived Searchable')->assertDontSee('Issue 12');
    }

    public function test_input_is_validated_and_newsletter_text_is_escaped(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/newsletters', $this->payload(['title' => '', 'body' => '', 'status' => 'scheduled', 'issue_date' => 'invalid']))->assertSessionHasErrors(['title', 'body', 'status', 'issue_date']);
        $this->post('/newsletters', $this->payload(['title' => str_repeat('x', 161), 'summary' => str_repeat('x', 501), 'body' => str_repeat('x', 50001)]))->assertSessionHasErrors(['title', 'summary', 'body']);
        $this->post('/newsletters', $this->payload(['cover_image' => UploadedFile::fake()->create('bad.pdf', 10, 'application/pdf')]))->assertSessionHasErrors('cover_image');
        $this->post('/newsletters', $this->payload(['cover_image' => UploadedFile::fake()->image('big.jpg')->size(5121)]))->assertSessionHasErrors('cover_image');
        $this->assertDatabaseCount('newsletters', 0);
        $newsletter = $this->newsletter(['body' => '<script>alert("xss")</script>', 'status' => 'published']);
        $this->get(route('newsletters.show', $newsletter))->assertSee('<script>alert("xss")</script>')->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_cover_privacy_replacement_removal_and_deletion(): void
    {
        Storage::fake('local');
        $author = User::factory()->create();
        $this->actingAs($author)->post('/newsletters', $this->payload(['cover_image' => UploadedFile::fake()->image('first.jpg')]))->assertSessionHasNoErrors();
        $newsletter = Newsletter::firstOrFail();
        $first = $newsletter->cover_image_path;
        Storage::disk('local')->assertExists($first);
        $this->get(route('newsletters.cover', $newsletter))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs(User::factory()->create())->get(route('newsletters.cover', $newsletter))->assertNotFound();
        $this->actingAs($author)->put(route('newsletters.update', $newsletter), $this->payload(['status' => 'published', 'cover_image' => UploadedFile::fake()->image('second.jpg')]))->assertSessionHasNoErrors();
        $second = $newsletter->fresh()->cover_image_path;
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
        $this->app['auth']->forgetGuards();
        $this->get(route('newsletters.cover', $newsletter))->assertOk();
        $this->actingAs($author)->put(route('newsletters.update', $newsletter), $this->payload(['remove_cover' => '1']));
        Storage::disk('local')->assertMissing($second);
        $this->assertNull($newsletter->fresh()->cover_image_path);
        $this->get(route('newsletters.cover', $newsletter))->assertNotFound();
        $this->put(route('newsletters.update', $newsletter), $this->payload(['cover_image' => UploadedFile::fake()->image('third.jpg')]));
        $third = $newsletter->fresh()->cover_image_path;
        $this->delete(route('newsletters.destroy', $newsletter));
        Storage::disk('local')->assertMissing($third);
    }

    public function test_duplicate_and_reserved_titles_have_distinct_working_urls(): void
    {
        $one = $this->newsletter(['title' => 'manage', 'status' => 'published']);
        $two = $this->newsletter(['title' => 'manage', 'status' => 'published']);
        $this->assertNotSame($one->slug, $two->slug);
        $this->assertNotSame('manage', $one->slug);
        $this->get(route('newsletters.show', $one))->assertOk();
        $this->get(route('newsletters.show', $two))->assertOk();
    }
}
