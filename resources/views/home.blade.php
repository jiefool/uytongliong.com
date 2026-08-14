<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uy-Malmis Clan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="home-page">
    <main class="home-shell">
        <header class="home-nav">
            <a class="home-brand" href="{{ route('home') }}"><span>家</span> Uy-Malmis Clan</a>
            <div>
                @auth <a class="home-login" href="{{ route('family-tree') }}">Open family tree</a>
                @else <a class="home-login" href="{{ route('login') }}">Admin/Secretariat Access</a> @endauth
            </div>
        </header>
        <section class="home-hero">
            <p class="eyebrow">根深葉茂 · ROOTED IN HERITAGE</p>
            <div class="home-seal">家</div>
            <h1>Uy-Malmis<br><em>Clan</em></h1>
            <p class="home-intro">A living archive of the people, stories, and enduring connections that shape our family.</p>
            <div class="home-actions">
                @auth <a class="home-primary" href="{{ route('family-tree') }}">Enter the family tree <span>→</span></a>
                @else <a class="home-primary" href="{{ route('login') }}">Admin/Secretariat Access <span>→</span></a> @endauth
                <a class="home-secondary" href="{{ route('pin.entry') }}">View with an access PIN</a>
                <a class="home-secondary" href="{{ route('events.public') }}">View clan events</a>
            </div>
        </section>
        <section class="home-values">
            <article><span>傳</span><h2>Our stories</h2><p>Preserve the moments and messages that matter.</p></article>
            <article><span>根</span><h2>Our roots</h2><p>Trace the family connections that brought us here.</p></article>
            <article><span>家</span><h2>Our legacy</h2><p>Share a living history with generations to come.</p></article>
        </section>
        <footer>Uy-Malmis Clan · Family archive</footer>
    </main>
</body>
</html>
