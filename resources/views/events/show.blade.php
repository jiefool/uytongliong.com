<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->title }} | Uy-Malmis Clan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="message-page">
    <main class="message-card event-detail-card">
        <a class="back-link" href="{{ route('events.public') }}">← All clan events</a>
        @if ($event->poster_image_path)<button class="event-detail-poster-button" type="button" data-event-poster="{{ asset('storage/'.$event->poster_image_path) }}" data-event-poster-alt="{{ $event->title }} poster"><img class="event-detail-poster" src="{{ asset('storage/'.$event->poster_image_path) }}" alt="{{ $event->title }} poster"></button>@else<span class="message-seal">慶</span>@endif
        <p class="eyebrow">{{ $event->event_date->format('l, F j, Y') }}</p>
        <h1>{{ $event->title }}</h1>
        @if ($event->location)<p class="event-detail-location">⌖ {{ $event->location }}</p>@endif
        @if ($event->description)<p class="message-copy event-detail-description">{{ $event->description }}</p>@endif
        <div class="message-rule"></div>
        <p class="message-signature">Shared by {{ $event->creator->name }}</p>
        <x-event-poster-modal />
    </main>
</body>
</html>
