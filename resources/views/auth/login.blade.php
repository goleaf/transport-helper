@extends('layouts.auth')

@section('title')
Sign in
@endsection

@section('content')
<section class="auth-card" aria-labelledby="login-title">
    <div class="auth-copy">
        <p class="portal-eyebrow">Secure workspace</p>
        <h1 id="login-title">Supply / Procurement Agent</h1>
        <p>Sign in to manage imports, replenishment approvals, supplier email, AI review and logistics records.</p>

        <dl class="auth-demo">
            <dt>Demo email</dt>
            <dd>test@example.com</dd>
            <dt>Demo password</dt>
            <dd>password</dd>
        </dl>
    </div>

    <form method="post" action="{{ route('login.store') }}" class="auth-form">
        @csrf

        @if ($errors->any())
            <p role="alert">{{ $errors->first() }}</p>
        @endif

        <label for="email">
            Email
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </label>

        <label for="password">
            Password
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </label>

        <label class="auth-check" for="remember">
            <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
            Remember this browser
        </label>

        <button type="submit">Sign in</button>
    </form>
</section>
@endsection
