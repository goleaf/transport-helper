@php
    $navigationItems = \App\Support\SupplyNavigation::items();
@endphp

<nav aria-label="Supply navigation">
    <h2>Supply Navigation</h2>
    <ul>
        @forelse ($navigationItems as $item)
            @php
                $isActive = request()->routeIs($item['active']);
            @endphp
            <li>
                <a href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif>{{ $item['label'] }}</a>

                @if (! empty($item['children']))
                    <ul>
                        @forelse ($item['children'] as $child)
                            <li>
                                <a href="{{ route($child['route']).'#'.$child['fragment'] }}">{{ $child['label'] }}</a>
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
