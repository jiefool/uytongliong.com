@extends('newsletters.layout')
@section('title', $newsletter->exists ? 'Edit Newsletter' : 'Create Newsletter')
@section('content')
    <header class="newsletter-heading"><div><p class="eyebrow">THE FAMILY PRESS · 書</p><h1>{{ $newsletter->exists ? 'Edit' : 'Create' }} <em>Newsletter</em></h1></div>@if ($newsletter->exists)<a class="newsletter-button newsletter-button-secondary" href="{{ route('newsletters.show', $newsletter) }}">{{ $newsletter->status === 'published' ? 'View published issue' : 'Preview saved issue' }} →</a>@endif</header>
    <form class="newsletter-editor" method="POST" enctype="multipart/form-data" action="{{ $newsletter->exists ? route('newsletters.update', $newsletter) : route('newsletters.store') }}">
        @csrf @if ($newsletter->exists) @method('PUT') @endif
        @if ($errors->any())<div class="newsletter-errors" role="alert"><strong>Please check the following:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="newsletter-editor-main">
            <label>Title<input name="title" value="{{ old('title', $newsletter->title) }}" maxlength="160" required placeholder="A season of family stories"></label>
            <label>Summary<textarea name="summary" rows="3" maxlength="500" placeholder="A short introduction for the newsletter archive…">{{ old('summary', $newsletter->summary) }}</textarea></label>
            <label>Newsletter content<textarea class="newsletter-body-input" name="body" rows="20" maxlength="50000" required placeholder="Share family news, celebrations, and updates…">{{ old('body', $newsletter->body) }}</textarea></label>
            <p class="newsletter-help">Write in plain text. Line breaks are preserved in the published issue.</p>
        </div>
        <aside class="newsletter-editor-settings" aria-label="Publication settings">
            <h2>Publication settings</h2>
            <label>Issue date<input name="issue_date" type="date" value="{{ old('issue_date', $newsletter->issue_date?->format('Y-m-d') ?? today('Asia/Manila')->format('Y-m-d')) }}" required></label>
            <label>Status<select name="status">@foreach (['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $newsletter->status) === $value)>{{ $label }}</option>@endforeach</select></label>
            <p class="newsletter-help">Published issues are immediately public. Drafts and archived issues are visible only to their author and admins. The issue date is a label, not a publication schedule.</p>
            <label>Cover image<input name="cover_image" type="file" accept="image/*"></label><p class="newsletter-help">Optional image, up to 5 MB.</p>
            @if ($newsletter->cover_image_path)
                <img class="newsletter-cover-preview" src="{{ route('newsletters.cover', $newsletter) }}" alt="Current cover image">
                <label class="newsletter-checkbox"><input type="checkbox" name="remove_cover" value="1" @checked(old('remove_cover'))>Remove cover image</label>
            @endif
            <button type="submit">{{ $newsletter->exists ? 'Save newsletter' : 'Create newsletter' }}</button>
            <a class="newsletter-cancel" href="{{ route('newsletters.index') }}">Back to newsletters</a>
        </aside>
    </form>
@endsection
