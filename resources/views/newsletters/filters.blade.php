<form class="newsletter-filters" method="GET" action="{{ $managing ? route('newsletters.index') : route('newsletters.public') }}" aria-label="Filter newsletters">
    <label>Search newsletters<input type="search" name="q" value="{{ $filters['q'] }}" maxlength="160" placeholder="Search titles and summaries"></label>
    @if ($managing)
        <label>Status<select name="status">@foreach (['all' => 'All statuses', 'draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></label>
    @endif
    <button type="submit">Search</button><a href="{{ $managing ? route('newsletters.index') : route('newsletters.public') }}">Reset</a>
</form>
@if ($errors->any())<p class="form-error" role="alert">{{ $errors->first() }}</p>@endif
