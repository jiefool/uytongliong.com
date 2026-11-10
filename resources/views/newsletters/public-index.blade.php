@extends('newsletters.layout')
@section('content')
    <header class="newsletter-public-heading">
        <span class="message-seal">書</span><p class="eyebrow">LETTERS FROM THE FAMILY</p>
        <h1>Our Family<br><em>Newsletters</em></h1>
        <p class="message-copy">News, milestones, and stories that bring our generations together.</p>
    </header>
    @include('newsletters.filters', ['managing' => false])
    <p class="newsletter-meta">{{ $newsletters->total() }} published {{ Str::plural('issue', $newsletters->total()) }}</p>
    @include('newsletters.list', ['managing' => false])
@endsection
