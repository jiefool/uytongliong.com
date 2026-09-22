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
        <a class="back-link" href="{{ route('home') }}">← Back to homepage</a>
        <p class="eyebrow">UY TONG LIONG · 家</p>
        <span class="message-seal">慶</span>
        <h1>Clan<br><em>Events</em></h1>
        <p class="message-copy">Reunions, celebrations, and milestones shared by Uy Tong Liong.</p>
        <section class="message-archive public-events" aria-label="Clan events">
            <div class="message-rule"></div>
            <p class="eyebrow">EVENT CALENDAR · {{ $events->count() }}</p>
            @forelse ($events as $event)
                <article class="archive-message event-row">@if ($event->poster_image_path)<button class="event-poster-button" type="button" data-event-poster="{{ asset('storage/'.$event->poster_image_path) }}" data-event-poster-alt="{{ $event->title }} poster"><img class="event-poster" src="{{ asset('storage/'.$event->poster_image_path) }}" alt="{{ $event->title }} poster"></button>@else<div class="event-date"><strong>{{ $event->event_date->format('d') }}</strong><span>{{ $event->event_date->format('M') }}</span></div>@endif<div class="event-content"><a class="public-event-card" href="{{ route('events.show', $event) }}"><div class="archive-message-heading"><div><h2>{{ $event->title }}</h2><p>{{ $event->event_date->format('l, F j, Y') }}@if ($event->location) · {{ $event->location }}@endif</p></div><span>→</span></div>@if ($event->description)<p class="archive-message-body">{{ Str::limit($event->description, 150) }}</p>@endif</a></div></article>
            @empty
                <p class="archive-empty">No upcoming clan events have been published yet.</p>
            @endforelse
        </section>
        @auth <a class="public-manage-link" href="{{ route('events.index') }}">Manage clan events</a> @endauth
        <x-event-poster-modal />
    </main>
</body>
</html>
