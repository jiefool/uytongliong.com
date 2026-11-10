<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clan Events | Uy Tong Liong - Loo Siok Tin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="message-page">
    <main class="message-card users-card">
        <nav class="event-navigation"><a href="{{ route('home') }}">← Homepage</a>@auth<a href="{{ route('events.index') }}">Manage events →</a>@endauth</nav>
        <p class="eyebrow">UY TONG LIONG - LOO SIOK TIN · 家</p>
        <span class="message-seal">慶</span>
        <h1>Clan<br><em>Events</em></h1>
        <p class="message-copy">Reunions, celebrations, and milestones shared by Uy Tong Liong.</p>
        <section class="message-archive public-events" aria-label="Clan events">
            <div class="message-rule"></div>
            @include('events.partials.filters', ['managing' => false])
            @if ($errors->any())<p class="form-error" role="alert">{{ $errors->first() }}</p>@endif
            <p class="eyebrow">EVENT CALENDAR · {{ $events->total() }}</p>
            @include('events.partials.list', ['managing' => false])
        </section>
        <x-event-poster-modal />
    </main>
</body>
</html>
