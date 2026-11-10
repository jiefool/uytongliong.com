<?php

namespace App\Http\Controllers;

use App\Models\ClanEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClanEventController extends Controller
{
    public function publicIndex(Request $request): View
    {
        $filters = $this->filters($request);
        $query = ClanEvent::with('creator')->whereIn('status', ['published', 'cancelled']);

        return view('events.public-index', [
            'events' => $this->filtered($query, $filters)->paginate(12)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, ClanEvent $event): View
    {
        abort_if($event->status === 'draft' && ! $event->canManage($request->user()), 404);
        $event->load('creator')->loadCount([
            'rsvps as going_count' => fn ($query) => $query->where('response', 'going'),
            'rsvps as maybe_count' => fn ($query) => $query->where('response', 'maybe'),
        ]);

        return view('events.show', [
            'event' => $event,
            'myRsvp' => $request->user() ? $event->rsvps()->where('user_id', $request->user()->id)->first() : null,
            'attendees' => $event->canManage($request->user()) ? $event->rsvps()->with('user')->latest()->paginate(20) : null,
        ]);
    }

    public function index(Request $request): View
    {
        return $this->view($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $this->storePosterImage($request, $validated);
        $request->user()->clanEvents()->create($validated);

        return redirect()->route('events.index')->with('status', 'Clan event created.');
    }

    public function edit(Request $request, ClanEvent $event): View
    {
        abort_unless($event->canManage($request->user()), 403);

        return $this->view($request, $event);
    }

    public function update(Request $request, ClanEvent $event): RedirectResponse
    {
        abort_unless($event->canManage($request->user()), 403);
        $validated = $this->validatedData($request);
        $oldPoster = $event->poster_image_path;
        $this->storePosterImage($request, $validated);
        if ($request->boolean('remove_poster') && ! $request->hasFile('poster_image')) {
            $validated['poster_image_path'] = null;
        }
        // Keep shared event URLs stable when the title changes.
        $event->update($validated);
        if ($oldPoster && $oldPoster !== $event->poster_image_path) {
            Storage::disk('public')->delete($oldPoster);
        }

        return redirect()->route('events.index')->with('status', 'Clan event updated.');
    }

    public function destroy(Request $request, ClanEvent $event): RedirectResponse
    {
        abort_unless($event->canManage($request->user()), 403);
        $event->delete();
        if ($event->poster_image_path) {
            Storage::disk('public')->delete($event->poster_image_path);
        }

        return redirect()->route('events.index')->with('status', 'Clan event removed.');
    }

    public function rsvp(Request $request, ClanEvent $event): RedirectResponse
    {
        abort_if($event->status === 'draft', 404);
        abort_unless($event->acceptsRsvps(), 422, 'RSVPs are closed for this event.');
        $validated = $request->validate(['response' => ['required', Rule::in(['going', 'maybe', 'declined'])]]);
        $event->rsvps()->updateOrCreate(['user_id' => $request->user()->id], $validated);

        return redirect()->route('events.show', $event)->with('status', 'Your RSVP has been saved.');
    }

    private function view(Request $request, ?ClanEvent $editingEvent = null): View
    {
        $filters = $this->filters($request, true);
        $query = ClanEvent::query()->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id));
        $stats = [
            'total' => (clone $query)->count(),
            'upcoming' => (clone $query)->where('status', 'published')->whereDate('event_date', '>=', today('Asia/Manila'))->count(),
            'drafts' => (clone $query)->where('status', 'draft')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
        ];
        $events = $this->filtered($query->with('creator')->withCount([
            'rsvps as going_count' => fn ($query) => $query->where('response', 'going'),
        ]), $filters)->paginate(12)->withQueryString();

        return view('events.index', compact('events', 'editingEvent', 'filters', 'stats'));
    }

    private function filters(Request $request, bool $managing = false): array
    {
        return array_merge(['q' => '', 'period' => $managing ? 'all' : 'upcoming', 'status' => 'all'], $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:160'],
            'period' => ['sometimes', Rule::in(['all', 'upcoming', 'past'])],
            'status' => ['sometimes', Rule::in($managing ? ['all', 'draft', 'published', 'cancelled'] : ['all', 'published', 'cancelled'])],
        ]));
    }

    private function filtered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'], fn ($query) => $query->where(function ($query) use ($filters) {
                $query->where('title', 'like', '%'.$filters['q'].'%')->orWhere('location', 'like', '%'.$filters['q'].'%');
            }))
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['period'] !== 'all', fn ($query) => $query->whereDate('event_date', $filters['period'] === 'past' ? '<' : '>=', today('Asia/Manila')))
            ->orderBy('event_date', $filters['period'] === 'past' ? 'desc' : 'asc')
            ->orderBy('start_time')->orderBy('id');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'event_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'required_with:end_time', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published', 'cancelled'])],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'poster_image' => ['nullable', 'image', 'max:5120'],
            'remove_poster' => ['sometimes', 'boolean'],
        ]);
    }

    private function storePosterImage(Request $request, array &$validated): void
    {
        unset($validated['poster_image'], $validated['remove_poster']);
        if ($request->hasFile('poster_image')) {
            $validated['poster_image_path'] = $request->file('poster_image')->store('event-posters', 'public');
        }
    }
}
