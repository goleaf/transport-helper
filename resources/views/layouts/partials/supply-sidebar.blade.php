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
        <x-supply.button type="submit" variant="neutral" mode="outline" block>Sign out</x-supply.button>
    </form>
@endauth
