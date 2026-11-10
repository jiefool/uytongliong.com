<div class="newsletter-grid">
    @forelse ($newsletters as $newsletter)
        <article class="newsletter-card">
            <a class="newsletter-card-link" href="{{ route('newsletters.show', $newsletter) }}">
                @if ($newsletter->cover_image_path)<img class="newsletter-cover" src="{{ route('newsletters.cover', $newsletter) }}" alt="Cover of {{ $newsletter->title }}" loading="lazy">@else<div class="newsletter-cover-placeholder" aria-hidden="true">書<span>UY TONG LIONG - LOO SIOK TIN · FAMILY PRESS</span></div>@endif
                <div class="newsletter-card-content">
                    <div class="newsletter-card-meta"><time datetime="{{ $newsletter->issue_date->format('Y-m-d') }}">{{ $newsletter->issue_date->format('M j, Y') }}</time>@if ($managing)<span class="newsletter-badge newsletter-badge-{{ $newsletter->status }}">{{ ucfirst($newsletter->status) }}</span>@endif</div>
                    <h2>{{ $newsletter->title }}</h2>
                    <p>{{ $newsletter->summary ?: Str::limit($newsletter->body, 180) }}</p>
                    <span class="newsletter-read">{{ $managing && $newsletter->status !== 'published' ? 'Preview issue' : 'Read issue' }} →</span>
                </div>
            </a>
            @if ($managing)
                <div class="newsletter-card-footer"><p class="newsletter-meta">By {{ $newsletter->author->name }}</p><div class="message-actions"><a href="{{ route('newsletters.edit', $newsletter) }}">Edit</a><form method="POST" action="{{ route('newsletters.destroy', $newsletter) }}" data-confirm="Delete this newsletter permanently? You can archive it instead to keep a private copy.">@csrf @method('DELETE')<button type="submit" aria-label="Delete {{ $newsletter->title }}">Delete</button></form></div></div>
            @endif
        </article>
    @empty
        <div class="newsletter-empty"><span aria-hidden="true">書</span><h2>No newsletters found</h2><p>{{ $managing ? 'Create an issue or adjust your search filters.' : 'Check back for family news, or try another search.' }}</p>@if ($managing)<a href="{{ route('newsletters.create') }}">Create a newsletter →</a>@endif</div>
    @endforelse
</div>
<div class="newsletter-pagination">{{ $newsletters->links() }}</div>
