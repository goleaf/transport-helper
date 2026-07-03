<nav class="supply-nav" aria-label="Supply navigation">
    <div class="nav-section-title">Workspace</div>
    <ul class="nav-list">
        @forelse ($items as $item)
            <li class="nav-item">
                <a class="nav-link" href="{{ $item['href'] }}" @if ($item['is_active']) aria-current="page" @endif>
                    <span class="nav-dot" aria-hidden="true"></span>
                    <span>{{ $item['label'] }}</span>
                </a>

                @if ($item['show_children'])
                    <ul class="nav-child-list">
                        @forelse ($item['children'] as $child)
                            <li>
                                <a class="nav-child-link" href="{{ $child['href'] }}">{{ $child['label'] }}</a>
                            </li>
                        @empty
                            <li>No dashboard sections.</li>
                        @endforelse
                    </ul>
                @endif
            </li>
        @empty
            <li>No navigation sections.</li>
        @endforelse
    </ul>
</nav>
