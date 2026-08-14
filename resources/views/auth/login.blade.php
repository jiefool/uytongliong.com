<x-auth.layout title="Sign in" heading="Welcome back." copy="Sign in to continue building your family story.">
    <form method="POST" action="{{ route('login.store') }}" class="auth-form">
        @csrf
        <label>Email <input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
        <label>Password <input name="password" type="password" required autocomplete="current-password"></label>
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
        <label class="remember"><input name="remember" type="checkbox"> Keep me signed in</label>
        <button type="submit">Sign in</button>
    </form>
</x-auth.layout>
