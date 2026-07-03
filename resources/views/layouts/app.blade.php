<!doctype html>
<html lang="{{ $htmlLocale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Supply / Procurement Agent')</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect width='16' height='16' rx='3' fill='%230f766e'/%3E%3Cpath d='M4 8h8M8 4v8' stroke='white' stroke-width='1.5'/%3E%3C/svg%3E">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/daisy.css', 'resources/css/app.scss', 'resources/js/app.js'])
    @endif
</head>
<body data-theme="transport">
    <a class="skip-link" href="#content">Skip to content</a>

    <div class="app-shell">
        <aside class="app-sidebar">
            <div class="brand-lockup">
                <a class="brand-mark" href="{{ route('supply.dashboard') }}" aria-label="Open Supply Dashboard">SP</a>
                <div>
                    <p class="brand-eyebrow">Procurement</p>
                    <p class="brand-name">Supply Agent</p>
                </div>
            </div>

            <x-supply.navigation />

            @auth
                <form method="post" action="{{ route('logout') }}" class="sidebar-logout">
                    @csrf
                    <button type="submit" class="button-secondary">Sign out</button>
                </form>
            @endauth
        </aside>

        <main id="content" class="app-main">
            <div class="app-content">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
