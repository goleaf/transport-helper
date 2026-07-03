@if ($isEmpty)
    <span class="empty-value">{{ $empty }}</span>
@elseif ($isBoolean || $isScalar)
    <span>{{ $displayValue }}</span>
@elseif ($isList)
    <ul class="structured-list">
        @forelse ($items as $item)
            <li>
                <x-supply.structured-value :value="$item" :empty="$empty" />
            </li>
        @empty
            <li><span class="empty-value">{{ $empty }}</span></li>
        @endforelse
    </ul>
@elseif ($isMap)
    <dl class="structured-data">
        @forelse ($items as $item)
            <dt>{{ $item['label'] }}</dt>
            <dd><x-supply.structured-value :value="$item['value']" :empty="$empty" /></dd>
        @empty
            <dt>Value</dt>
            <dd><span class="empty-value">{{ $empty }}</span></dd>
        @endforelse
    </dl>
@else
    <span>{{ $displayValue }}</span>
@endif
