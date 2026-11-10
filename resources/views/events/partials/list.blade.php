@forelse ($events as $event)
    <article class="archive-message event-row">
        @if ($event->poster_image_path)
            <button class="event-poster-button" type="button" data-event-poster="{{ asset('storage/'.$event->poster_image_path) }}" data-event-poster-alt="{{ $event->title }} poster"><img class="event-poster" src="{{ asset('storage/'.$event->poster_image_path) }}" alt="{{ $event->title }} poster" loading="lazy"></button>
        @else
            <div class="event-date"><strong>{{ $event->event_date->format('d') }}</strong><span>{{ $event->event_date->format('M Y') }}</span></div>
        @endif
        <div class="event-content">
            <span class="event-badge event-badge-{{ $event->status }}">{{ ucfirst($event->status) }}</span>
            <a class="public-event-card" href="{{ route('events.show', $event) }}">
                <div class="archive-message-heading"><div><h2>{{ $event->title }}</h2><p>@include('events.partials.schedule')</p>@if ($event->location)<p>{{ $event->location }}</p>@endif</div><span aria-hidden="true">→</span></div>
                @if ($event->description)<p class="archive-message-body">{{ Str::limit($event->description, 150) }}</p>@endif
            </a>
            @if ($managing)
                <p class="event-list-meta">{{ $event->going_count }} going · Organized by {{ $event->creator->name }}</p>
                <div class="message-actions">
                    <a href="{{ route('events.edit', $event) }}">Edit</a>
                    <a href="{{ route('events.show', $event) }}#attendance">Attendance</a>
                    <form method="POST" action="{{ route('events.destroy', $event) }}" data-confirm="Delete this event and all its RSVPs? This cannot be undone.">@csrf @method('DELETE')<button type="submit">Delete</button></form>
                </div>
            @endif
        </div>
    </article>
@empty
    <p class="archive-empty">No events match these filters.</p>
@endforelse
<div class="event-pagination">{{ $events->links() }}</div>
