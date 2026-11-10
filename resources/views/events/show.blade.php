<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->title }} | Uy Tong Liong - Loo Siok Tin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="message-page">
    <main class="message-card event-detail-card">
        <nav class="event-navigation"><a href="{{ route('events.public') }}">← All clan events</a>@if ($event->canManage(auth()->user()))<a href="{{ route('events.edit', $event) }}">Edit event →</a>@endif</nav>
        @if ($event->poster_image_path)<button class="event-detail-poster-button" type="button" data-event-poster="{{ asset('storage/'.$event->poster_image_path) }}" data-event-poster-alt="{{ $event->title }} poster"><img class="event-detail-poster" src="{{ asset('storage/'.$event->poster_image_path) }}" alt="{{ $event->title }} poster"></button>@else<span class="message-seal">慶</span>@endif
        <span class="event-badge event-badge-{{ $event->status }}">{{ ucfirst($event->status) }}</span>
        <p class="eyebrow">@include('events.partials.schedule')</p>
        <h1>{{ $event->title }}</h1>
        @if ($event->location)<p class="event-detail-location">⌖ {{ $event->location }}</p>@endif
        @if ($event->status === 'cancelled')<p class="event-notice">This event has been cancelled. RSVPs are closed.</p>@endif
        @if ($event->status === 'draft')<p class="event-notice">This is a private draft. Publish it to share it with the family.</p>@endif
        @if ($event->description)<p class="message-copy event-detail-description">{{ $event->description }}</p>@endif
        @if (session('status'))<p class="message-status" role="status">{{ session('status') }}</p>@endif
        <section class="event-rsvp" aria-labelledby="rsvp-heading">
            <h2 id="rsvp-heading">Are you joining us?</h2>
            <p>{{ $event->going_count }} going · {{ $event->maybe_count }} maybe</p>
            @if ($myRsvp)<p>Your response: <strong>{{ ['going' => 'Going', 'maybe' => 'Maybe', 'declined' => 'Not going'][$myRsvp->response] }}</strong></p>@endif
            @if ($event->acceptsRsvps())
                @auth
                    @if (auth()->user()->isFamilyAdmin())
                        <p class="event-field-help">Family Admin accounts can view events but cannot RSVP.</p>
                    @else
                    <form class="event-rsvp-form" method="POST" action="{{ route('events.rsvp', $event) }}">
                        @csrf
                        @foreach (['going' => 'Going', 'maybe' => 'Maybe', 'declined' => 'Not going'] as $value => $label)
                            <button type="submit" name="response" value="{{ $value }}" aria-pressed="{{ $myRsvp?->response === $value ? 'true' : 'false' }}">{{ $label }}</button>
                        @endforeach
                    </form>
                    <p class="event-field-help">You can change your response while RSVPs are open. Your name and response are visible to the organizer and admins.</p>
                    @endif
                @else
                    <a class="public-manage-link" href="{{ route('login') }}">Sign in to RSVP</a>
                @endauth
            @elseif ($event->status === 'published')
                <p>RSVPs are closed for this event.</p>
            @endif
            @if ($errors->any())<p class="form-error" role="alert">{{ $errors->first() }}</p>@endif
        </section>
        @if ($attendees !== null)
            <section id="attendance" class="event-attendance" aria-labelledby="attendance-heading">
                <h2 id="attendance-heading">Attendance <span>({{ $attendees->total() }})</span></h2>
                <p class="event-field-help">Only the organizer and admins can view this list.</p>
                @forelse ($attendees as $rsvp)
                    <div class="event-attendee"><span>{{ $rsvp->user->name }}</span><strong>{{ ['going' => 'Going', 'maybe' => 'Maybe', 'declined' => 'Not going'][$rsvp->response] }}</strong></div>
                @empty
                    <p class="archive-empty">No responses yet.</p>
                @endforelse
                <div class="event-pagination">{{ $attendees->fragment('attendance')->links() }}</div>
            </section>
        @endif
        <div class="message-rule"></div>
        <p class="message-signature">Organized by {{ $event->creator->name }}</p>
        <x-event-poster-modal />
    </main>
</body>
</html>
