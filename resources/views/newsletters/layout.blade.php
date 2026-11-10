<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Family Newsletters') | Uy Tong Liong - Loo Siok Tin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="message-page newsletter-page">
    <main class="message-card newsletter-shell @yield('shell-class')">
        <nav class="newsletter-nav" aria-label="Newsletter navigation">
            <a href="{{ route('home') }}">← Homepage</a>
            <div><a href="{{ route('newsletters.public') }}">Read newsletters</a>@auth<a href="{{ route('newsletters.index') }}">Manage newsletters</a><a href="{{ route('family-tree') }}">Family tree</a>@endauth</div>
        </nav>
        @if (session('status'))<p class="newsletter-notice" role="status">{{ session('status') }}</p>@endif
        @yield('content')
    </main>
</body>
</html>
