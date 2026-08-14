<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} | Uy-Malmis Clan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="auth-brand" href="{{ route('login') }}"><span>家</span> Uy-Malmis Clan</a>
        <p class="eyebrow">FAMILY TREE</p>
        <h1>{{ $heading }}</h1>
        <p class="auth-copy">{{ $copy }}</p>
        {{ $slot }}
    </main>
</body>
</html>
