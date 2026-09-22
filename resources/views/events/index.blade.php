<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clan Events | Uy Tong Liong</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="message-page">
    <main class="message-card users-card">
        <a class="back-link" href="{{ route('family-tree') }}">← Back to family tree</a>
        <p class="eyebrow">CLAN CALENDAR · 家</p>
        <span class="message-seal">慶</span>
        <h1>Clan<br><em>Events</em></h1>
        <p class="message-copy">Keep Uy Tong Liong connected by recording reunions, birthdays, gatherings, and milestones.</p>
        @if (session('status'))<p class="message-status">{{ session('status') }}</p>@endif

        <form class="message-form" method="POST" enctype="multipart/form-data" action="{{ $editingEvent ? route('events.update', $editingEvent) : route('events.store') }}">
            @csrf @if ($editingEvent) @method('PUT') @endif
            <label>Event title <input name="title" value="{{ old('title', $editingEvent?->title) }}" required placeholder="Uy Tong Liong Family Reunion"></label>
            <div class="field-row"><label>Date <input name="event_date" type="date" value="{{ old('event_date', $editingEvent?->event_date?->format('Y-m-d')) }}" required></label><label>Location <input name="location" value="{{ old('location', $editingEvent?->location) }}" placeholder="City or venue"></label></div>
            <label>Event poster <input class="image-input" name="poster_image" type="file" accept="image/*"></label>
            @if ($editingEvent?->poster_image_path)<div class="event-poster-preview"><img src="{{ asset('storage/'.$editingEvent->poster_image_path) }}" alt="Current event poster"><span>Current event poster</span></div>@endif
            <label>Description <textarea name="description" placeholder="Add event details, reminders, or a programme…">{{ old('description', $editingEvent?->description) }}</textarea></label>
            @if ($errors->any())<p class="form-error">{{ $errors->first() }}</p>@endif
            <button type="submit">{{ $editingEvent ? 'Save event changes' : 'Create clan event' }}</button>
            @if ($editingEvent)<a class="cancel-message" href="{{ route('events.index') }}">Cancel editing</a>@endif
        </form>

        <section class="message-archive" aria-label="Clan events">
            <div class="message-rule"></div>
            <p class="eyebrow">UPCOMING &amp; PAST EVENTS · {{ $events->count() }}</p>
            @forelse ($events as $event)
                <article class="archive-message event-row">@if ($event->poster_image_path)<button class="event-poster-button" type="button" data-event-poster="{{ asset('storage/'.$event->poster_image_path) }}" data-event-poster-alt="{{ $event->title }} poster"><img class="event-poster" src="{{ asset('storage/'.$event->poster_image_path) }}" alt="{{ $event->title }} poster"></button>@else<div class="event-date"><strong>{{ $event->event_date->format('d') }}</strong><span>{{ $event->event_date->format('M') }}</span></div>@endif<div class="event-content"><div class="archive-message-heading"><div><h2>{{ $event->title }}</h2><p>{{ $event->event_date->format('l, F j, Y') }}@if ($event->location) · {{ $event->location }}@endif</p></div><span>慶</span></div>@if ($event->description)<p class="archive-message-body">{{ $event->description }}</p>@endif<p class="event-creator">Added by {{ $event->creator->name }}</p><div class="message-actions"><a href="{{ route('events.edit', $event) }}">Edit</a><form method="POST" action="{{ route('events.destroy', $event) }}">@csrf @method('DELETE')<button type="submit">Delete</button></form></div></div></article>
            @empty
                <p class="archive-empty">No clan events yet. Create the first one above.</p>
            @endforelse
        </section>
        <x-event-poster-modal />
    </main>
</body>
</html>
