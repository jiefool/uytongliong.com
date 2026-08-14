<x-auth.layout title="Create account" heading="Start your story." copy="Create an account to preserve and grow your family tree.">
    <form method="POST" action="{{ route('register.store') }}" class="auth-form">
        @csrf
        <label>Your name <input name="name" value="{{ old('name') }}" required autofocus autocomplete="name"></label>
        <label>Email <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></label>
        <label>Password <input name="password" type="password" required autocomplete="new-password"></label>
        <label>Confirm password <input name="password_confirmation" type="password" required autocomplete="new-password"></label>
        @if ($errors->any())<p class="form-error">{{ $errors->first() }}</p>@endif
        <button type="submit">Create account</button>
    </form>
    <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</x-auth.layout>
