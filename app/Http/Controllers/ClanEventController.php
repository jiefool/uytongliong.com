<?php

namespace App\Http\Controllers;

use App\Models\ClanEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClanEventController extends Controller
{
    public function publicIndex(): View
    {
        return view('events.public-index', ['events' => ClanEvent::with('creator')->orderBy('event_date')->get()]);
    }

    public function show(ClanEvent $event): View
    {
        return view('events.show', ['event' => $event->load('creator')]);
    }

    public function index(): View
    {
        return $this->view();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $this->storePosterImage($request, $validated);
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $request->user()->clanEvents()->create($validated);

        return redirect()->route('events.index')->with('status', 'Clan event created.');
    }

    public function edit(ClanEvent $event): View
    {
        return $this->view($event);
    }

    public function update(Request $request, ClanEvent $event): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $this->storePosterImage($request, $validated);
        $validated['slug'] = $this->uniqueSlug($validated['title'], $event);
        $event->update($validated);

        return redirect()->route('events.index')->with('status', 'Clan event updated.');
    }

    public function destroy(ClanEvent $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('events.index')->with('status', 'Clan event removed.');
    }

    private function view(?ClanEvent $editingEvent = null): View
    {
        return view('events.index', ['events' => ClanEvent::with('creator')->orderBy('event_date')->get(), 'editingEvent' => $editingEvent]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'event_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'poster_image' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    private function storePosterImage(Request $request, array &$validated): void
    {
        unset($validated['poster_image']);

        if ($request->hasFile('poster_image')) {
            $validated['poster_image_path'] = $request->file('poster_image')->store('event-posters', 'public');
        }
    }

    private function uniqueSlug(string $title, ?ClanEvent $ignore = null): string
    {
        $base = Str::slug($title) ?: 'clan-event';
        $slug = $base;
        $suffix = 2;

        while (ClanEvent::query()->where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
