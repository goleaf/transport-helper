@php
    $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
@endphp

<span data-status="{{ $statusValue }}">{{ str_replace('_', ' ', $statusValue) }}</span>
