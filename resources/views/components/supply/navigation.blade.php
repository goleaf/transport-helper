<nav class="supply-nav" aria-label="Supply navigation">
    @forelse ($groups as $group)
        <div class="nav-section-title">{{ $group['label'] }}</div>
        <ul class="menu nav-list">
            @forelse ($group['items'] as $item)
                <li class="nav-item">
                    <a class="nav-link" href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif>
                        <span class="nav-dot" aria-hidden="true"></span>
                        <span>{{ $item['label'] }}</span>
                        @if ($item['badge'])
                            <x-supply.badge size="sm" tone="warning">{{ $item['badge'] }}</x-supply.badge>
                        @endif
                    </a>
                </li>
            @empty
                <li>No navigation items.</li>
            @endforelse
        </ul>
    @empty
        <div class="nav-section-title">Workspace</div>
        <ul class="menu nav-list">
            <li>No navigation sections.</li>
        </ul>
    @endforelse
</nav>
