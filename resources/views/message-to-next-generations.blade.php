<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message to Next Generations | Uy Tong Liong - Loo Siok Tin</title>
    @vite(['resources/css/app.css'])
</head>
<body class="message-page">
    <main class="message-card">
        <a class="back-link" href="{{ !empty($pinViewer) ? route('pin.tree') : route('family-tree') }}">← Back to family tree</a>
        <p class="eyebrow">UY TONG LIONG - LOO SIOK TIN · 家</p>
        <span class="message-seal">傳</span>
        <h1>Message to<br><em>Next Generations</em></h1>
        <p class="message-copy">Preserve the wisdom, love, and stories you want future Uy Tong Liong generations to carry forward.</p>

        @if (!empty($pinViewer))<p class="viewer-note">View-only family archive</p>@endif
        @if (session('status'))<p class="message-status">{{ session('status') }}</p>@endif

        @if (empty($pinViewer))<form class="message-form" method="POST" enctype="multipart/form-data" action="{{ $editingMessage ? route('generational-messages.update', $editingMessage) : route('generational-messages.store') }}">
            @csrf
            @if ($editingMessage) @method('PUT') @endif
            <label>Message title <input name="title" value="{{ old('title', $editingMessage?->title) }}" required placeholder="A lesson to remember"></label>
            <label>Message from <input name="from_name" value="{{ old('from_name', $editingMessage?->from_name ?? auth()->user()->name) }}" placeholder="e.g. Lola Maria, The Aying Family"></label>
            <label>Your message <textarea name="body" maxlength="5000" placeholder="Write a message for the generations to come…">{{ old('body', $editingMessage?->body) }}</textarea></label>
            <label>Video message <input class="image-input" name="video" type="file" accept="video/mp4,video/webm,video/quicktime,video/x-m4v,.mp4,.webm,.mov,.m4v"></label>
            <p class="message-upload-hint">Add text, a video, or both. MP4, WebM, MOV, or M4V; up to 10 MB.</p>
            @if ($editingMessage?->video_path)
                <div class="message-current-video"><video controls preload="metadata" src="{{ route('generational-messages.video', $editingMessage) }}" aria-label="Current video for {{ $editingMessage->title }}"></video><span>Current video</span></div>
                <label class="message-remove-video"><input type="checkbox" name="remove_video" value="1" @checked(old('remove_video'))>Remove current video</label>
            @endif
            @if ($errors->any())<p class="form-error" role="alert">{{ $errors->first() }}</p>@endif
            <button type="submit">{{ $editingMessage ? 'Save message' : 'Add to the archive' }}</button>
            @if ($editingMessage)<a class="cancel-message" href="{{ route('message-to-next-generations') }}">Cancel editing</a>@endif
        </form>@endif

        <section class="message-archive" aria-label="Family message archive">
            <div class="message-rule"></div>
            <p class="eyebrow">FAMILY ARCHIVE · {{ $messages->count() }} {{ Str::plural('MESSAGE', $messages->count()) }}</p>
            @forelse ($messages as $message)
                <article class="archive-message">
                    <div class="archive-message-heading"><div><h2>{{ $message->title }}</h2><p>From {{ $message->from_name ?: $message->author->name }} · {{ $message->created_at->format('F j, Y') }}</p></div><span>家</span></div>
                    @if ($message->body)<p class="archive-message-body">{{ $message->body }}</p>@endif
                    @if ($message->video_path)<video class="archive-message-video" controls preload="metadata" src="{{ route('generational-messages.video', $message) }}" aria-label="Video message: {{ $message->title }}"></video>@endif
                    @if (empty($pinViewer))<div class="message-actions">
                        <a href="{{ route('generational-messages.edit', $message) }}">Edit</a>
                        <form method="POST" action="{{ route('generational-messages.destroy', $message) }}">@csrf @method('DELETE')<button type="submit">Delete</button></form>
                    </div>@endif
                </article>
            @empty
                <p class="archive-empty">The archive is ready for the first message from the clan.</p>
            @endforelse
        </section>
    </main>
</body>
</html>
