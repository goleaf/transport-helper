<header class="supply-topbar">
    <div class="supply-topbar-search">
        <label class="sr-only" for="supply-global-search">Search supply records</label>
        <input
            id="supply-global-search"
            class="input input-bordered input-sm w-full"
            type="search"
            placeholder="Search SKU, order, email, supplier..."
            aria-label="Search SKU, order, email, supplier"
        >
    </div>

    <div class="supply-topbar-meta">
        <x-supply.environment-badges />

        <a class="btn btn-ghost btn-sm" href="{{ route('supply.notifications.index') }}" aria-label="Open notifications">
            Notifications
        </a>

        @auth
            <span class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</span>
        @endauth
    </div>
</header>
