<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Supply / Procurement Agent')</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect width='16' height='16' rx='3' fill='%230f766e'/%3E%3Cpath d='M4 8h8M8 4v8' stroke='white' stroke-width='1.5'/%3E%3C/svg%3E">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body>
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
        </aside>

        <main id="content" class="app-main">
            <div class="app-main-bar" aria-label="Portal context">
                <div>
                    <p class="app-kicker">Laravel controlled workflow</p>
                    <p class="app-context">Imports, replenishment, approvals, supplier email, AI review and logistics</p>
                </div>
                <div class="app-guardrails" aria-label="Core guardrails">
                    <span>Light UI</span>
                    <span>Human approval</span>
                    <span>Audit first</span>
                </div>
            </div>

            <div class="app-content">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
