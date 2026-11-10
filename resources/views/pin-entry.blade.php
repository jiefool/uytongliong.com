<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Tree Access | Uy Tong Liong - Loo Siok Tin</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-brand"><span>家</span> Uy Tong Liong - Loo Siok Tin</div>
        <p class="eyebrow">FAMILY TREE ACCESS</p>
        <h1>Welcome, family.</h1>
        <p class="auth-copy">Enter the six-digit access PIN shared with you by Uy Tong Liong.</p>
        <form method="POST" action="{{ route('pin.verify') }}" class="auth-form">
            @csrf
            <label>Access PIN <input name="pin" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required autofocus placeholder="••••••"></label>
            @error('pin')<p class="form-error">{{ $message }}</p>@enderror
            <button type="submit">View family tree</button>
        </form>
        <p class="auth-switch"><a href="{{ route('login') }}">Sign in as a clan member</a></p>
    </main>
</body>
</html>
