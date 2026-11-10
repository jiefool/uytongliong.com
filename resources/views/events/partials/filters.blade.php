<form class="event-filters" method="GET" action="{{ $managing ? route('events.index') : route('events.public') }}" aria-label="Filter events">
    <label>Search events<input type="search" name="q" value="{{ $filters['q'] }}" maxlength="160" placeholder="Title or location"></label>
    <label>When<select name="period">@foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All dates'] as $value => $label)<option value="{{ $value }}" @selected($filters['period'] === $value)>{{ $label }}</option>@endforeach</select></label>
    <label>Status<select name="status">@foreach (($managing ? ['all' => 'All statuses', 'draft' => 'Draft', 'published' => 'Published', 'cancelled' => 'Cancelled'] : ['all' => 'All statuses', 'published' => 'Published', 'cancelled' => 'Cancelled']) as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></label>
    <button type="submit">Filter</button>
    <a href="{{ $managing ? route('events.index') : route('events.public') }}">Reset</a>
</form>
