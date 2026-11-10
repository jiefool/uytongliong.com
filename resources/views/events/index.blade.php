<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | Uy Tong Liong - Loo Siok Tin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="message-page">
    <main class="message-card event-management-card">
        <nav class="event-navigation"><a href="{{ route('family-tree') }}">← Family tree</a><a href="{{ route('events.public') }}">Public event calendar →</a></nav>
        <p class="eyebrow">CLAN CALENDAR · 家</p>
        <h1>Event <em>Management</em></h1>
        <p class="message-copy">Plan a gathering, share the details, and see who’s coming.</p>
        @if (session('status'))<p class="message-status" role="status">{{ session('status') }}</p>@endif
        <div class="event-stats" aria-label="Event summary">
            @foreach (['total' => 'Total events', 'upcoming' => 'Upcoming', 'drafts' => 'Drafts', 'cancelled' => 'Cancelled'] as $key => $label)
                <div><strong>{{ $stats[$key] }}</strong><span>{{ $label }}</span></div>
            @endforeach
        </div>
        <div class="event-workspace">
            <section class="event-editor" aria-labelledby="event-editor-heading">
                <h2 id="event-editor-heading">{{ $editingEvent ? 'Edit event' : 'Create an event' }}</h2>
                <form class="message-form" method="POST" enctype="multipart/form-data" action="{{ $editingEvent ? route('events.update', $editingEvent) : route('events.store') }}">
                    @csrf @if ($editingEvent) @method('PUT') @endif
                    @if ($errors->any())<div class="form-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    <label>Event title<input name="title" value="{{ old('title', $editingEvent?->title) }}" maxlength="160" required placeholder="Family reunion"></label>
                    <div class="field-row">
                        <label>Date<input name="event_date" type="date" value="{{ old('event_date', $editingEvent?->event_date?->format('Y-m-d')) }}" required></label>
                        <label>Status<select name="status">@foreach (['draft' => 'Draft', 'published' => 'Published', 'cancelled' => 'Cancelled'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $editingEvent?->status ?? 'draft') === $value)>{{ $label }}</option>@endforeach</select></label>
                    </div>
                    <p class="event-field-help">Drafts are private. Published and cancelled events appear on the public calendar.</p>
                    <div class="field-row">
                        <label>Start time<input name="start_time" type="time" value="{{ old('start_time', $editingEvent?->start_time ? substr($editingEvent->start_time, 0, 5) : '') }}"></label>
                        <label>End time<input name="end_time" type="time" value="{{ old('end_time', $editingEvent?->end_time ? substr($editingEvent->end_time, 0, 5) : '') }}"></label>
                    </div>
                    <p class="event-field-help">Leave times blank for an all-day event. End time must be later on the same day.</p>
                    <label>Time zone<select name="timezone">@foreach (DateTimeZone::listIdentifiers() as $zone)<option value="{{ $zone }}" @selected(old('timezone', $editingEvent?->timezone ?? 'Asia/Manila') === $zone)>{{ str_replace('_', ' ', $zone) }}</option>@endforeach</select></label>
                    <label>Location<input name="location" maxlength="255" value="{{ old('location', $editingEvent?->location) }}" placeholder="City, venue, or online meeting"></label>
                    <label>Event poster<input class="image-input" name="poster_image" type="file" accept="image/*"></label>
                    <p class="event-field-help">Image files up to 5 MB.</p>
                    @if ($editingEvent?->poster_image_path)
                        <div class="event-poster-preview"><img src="{{ asset('storage/'.$editingEvent->poster_image_path) }}" alt="Current event poster"><span>Current poster</span></div>
                        <label class="event-checkbox"><input type="checkbox" name="remove_poster" value="1" @checked(old('remove_poster'))>Remove current poster</label>
                    @endif
                    <label>Description<textarea name="description" maxlength="3000" placeholder="Programme, directions, and reminders…">{{ old('description', $editingEvent?->description) }}</textarea></label>
                    <button type="submit">{{ $editingEvent ? 'Save event changes' : 'Create event' }}</button>
                    @if ($editingEvent)<a class="cancel-message" href="{{ route('events.index') }}">Cancel editing</a>@endif
                </form>
            </section>
            <section class="message-archive event-managed-list" aria-label="Managed events">
                <h2>{{ auth()->user()->isAdmin() ? 'All clan events' : 'Your events' }}</h2>
                @include('events.partials.filters', ['managing' => true])
                <p class="event-list-meta">{{ $events->total() }} {{ Str::plural('event', $events->total()) }} found</p>
                @include('events.partials.list', ['managing' => true])
            </section>
        </div>
        <x-event-poster-modal />
    </main>
</body>
</html>
