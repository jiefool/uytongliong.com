@extends('newsletters.layout')
@section('title', $newsletter->title)
@section('shell-class', 'newsletter-reader')
@section('content')
    @if ($newsletter->canManage(auth()->user()))<div class="newsletter-reader-tools"><span class="newsletter-badge newsletter-badge-{{ $newsletter->status }}">{{ ucfirst($newsletter->status) }}</span><a href="{{ route('newsletters.edit', $newsletter) }}">Edit newsletter →</a></div>@endif
    @if ($newsletter->status !== 'published')<p class="newsletter-notice">This {{ $newsletter->status === 'draft' ? 'draft' : 'archived issue' }} is private. Only its author and admins can view it.</p>@endif
    <article>
        <header class="newsletter-issue-heading"><p class="eyebrow">UY TONG LIONG - LOO SIOK TIN · THE FAMILY PRESS</p><p class="newsletter-meta"><time datetime="{{ $newsletter->issue_date->format('Y-m-d') }}">{{ $newsletter->issue_date->format('F j, Y') }}</time> · By {{ $newsletter->author->name }}</p><h1>{{ $newsletter->title }}</h1>@if ($newsletter->summary)<p class="message-copy">{{ $newsletter->summary }}</p>@endif</header>
        @if ($newsletter->cover_image_path)<img class="newsletter-issue-cover" src="{{ route('newsletters.cover', $newsletter) }}" alt="Cover of {{ $newsletter->title }}">@endif
        <div class="newsletter-issue-body">{{ $newsletter->body }}</div>
        <footer class="newsletter-issue-footer"><span>家</span><p>Stories worth keeping. Connections worth celebrating.</p><a href="{{ route('newsletters.public') }}">← Browse all newsletters</a></footer>
    </article>
@endsection
