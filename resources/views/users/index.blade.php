<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Uy Tong Liong</title>
    @vite(['resources/css/app.css'])
</head>
<body class="message-page">
    <main class="message-card users-card">
        <a class="back-link" href="{{ route('family-tree') }}">← Back to family tree</a>
        <p class="eyebrow">ADMINISTRATION · 家</p>
        <span class="message-seal">管</span>
        <h1>User<br><em>Management</em></h1>
        <p class="message-copy">Create and manage the accounts that help preserve the Uy Tong Liong family history.</p>
        @if (session('status'))<p class="message-status">{{ session('status') }}</p>@endif

        <form class="message-form" method="POST" action="{{ $editingUser ? route('users.update', $editingUser) : route('users.store') }}">
            @csrf
            @if ($editingUser) @method('PUT') @endif
            <label>Full name <input name="name" value="{{ old('name', $editingUser?->name) }}" required></label>
            <label>Email address <input name="email" type="email" value="{{ old('email', $editingUser?->email) }}" required></label>
            <label>Role <select name="role" required><option value="secretariat" @selected(old('role', $editingUser?->role) === 'secretariat')>Secretariat</option><option value="admin" @selected(old('role', $editingUser?->role) === 'admin')>Admin</option></select></label>
            <label>{{ $editingUser ? 'New password (optional)' : 'Password' }} <input name="password" type="password" {{ $editingUser ? '' : 'required' }}></label>
            <label>Password confirmation <input name="password_confirmation" type="password" {{ $editingUser ? '' : 'required' }}></label>
            @if ($errors->any())<p class="form-error">{{ $errors->first() }}</p>@endif
            <button type="submit">{{ $editingUser ? 'Save user changes' : 'Create user account' }}</button>
            @if ($editingUser)<a class="cancel-message" href="{{ route('users.index') }}">Cancel editing</a>@endif
        </form>

        <section class="message-archive" aria-label="User accounts">
            <div class="message-rule"></div>
            <p class="eyebrow">USER ACCOUNTS · {{ $users->count() }}</p>
            @foreach ($users as $user)
                <article class="archive-message user-row">
                    <div class="archive-message-heading"><div><h2>{{ $user->name }}</h2><p>{{ $user->email }}</p></div><span class="role-badge {{ $user->role }}">{{ ucfirst($user->role) }}</span></div>
                    <div class="message-actions"><a href="{{ route('users.edit', $user) }}">Edit</a>@if (! $user->is(auth()->user()))<form method="POST" action="{{ route('users.destroy', $user) }}">@csrf @method('DELETE')<button type="submit">Delete</button></form>@endif</div>
                </article>
            @endforeach
        </section>
    </main>
</body>
</html>
