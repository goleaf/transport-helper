@props([
    'value' => null,
    'empty' => 'Not provided',
])

@php
    if ($value instanceof \BackedEnum) {
        $value = $value->value;
    }

    if ($value instanceof \DateTimeInterface) {
        $value = $value->format('Y-m-d H:i');
    }

    if ($value instanceof \Illuminate\Support\Collection) {
        $value = $value->all();
    }

    if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
        $value = $value->toArray();
    }

    $isEmptyArray = is_array($value) && count($value) === 0;
@endphp

@if ($value === null || $value === '' || $isEmptyArray)
    <span class="empty-value">{{ $empty }}</span>
@elseif (is_bool($value))
    <span>{{ $value ? 'Yes' : 'No' }}</span>
@elseif (is_scalar($value))
    <span>{{ $value }}</span>
@elseif (is_array($value) && array_is_list($value))
    <ul class="structured-list">
        @forelse ($value as $item)
            <li>
                <x-supply.structured-value :value="$item" :empty="$empty" />
            </li>
        @empty
            <li><span class="empty-value">{{ $empty }}</span></li>
        @endforelse
    </ul>
@elseif (is_array($value))
    <dl class="structured-data">
        @forelse ($value as $key => $item)
            <dt>{{ \Illuminate\Support\Str::of((string) $key)->replace(['_', '-'], ' ')->headline() }}</dt>
            <dd><x-supply.structured-value :value="$item" :empty="$empty" /></dd>
        @empty
            <dt>Value</dt>
            <dd><span class="empty-value">{{ $empty }}</span></dd>
        @endforelse
    </dl>
@else
    <span>{{ (string) $value }}</span>
@endif
