@extends('newsletters.layout')
@section('title', 'Manage Newsletters')
@section('content')
    <header class="newsletter-heading">
        <div><p class="eyebrow">THE FAMILY PRESS · 書</p><h1>Newsletter <em>Management</em></h1><p class="message-copy">Keep the family connected, one story at a time.</p></div>
        <a class="newsletter-button" href="{{ route('newsletters.create') }}">+ Create newsletter</a>
    </header>
    <div class="newsletter-stats" aria-label="Newsletter summary">
        @foreach (['total' => 'Total issues', 'draft' => 'Drafts', 'published' => 'Published', 'archived' => 'Archived'] as $key => $label)
            <div><strong>{{ $stats[$key] }}</strong><span>{{ $label }}</span></div>
        @endforeach
    </div>
    <section aria-labelledby="newsletter-list-title">
        <h2 id="newsletter-list-title">{{ auth()->user()->isAdmin() ? 'All newsletters' : 'Your newsletters' }}</h2>
        @include('newsletters.filters', ['managing' => true])
        <p class="newsletter-meta">{{ $newsletters->total() }} {{ Str::plural('issue', $newsletters->total()) }} found</p>
        @include('newsletters.list', ['managing' => true])
    </section>
@endsection
