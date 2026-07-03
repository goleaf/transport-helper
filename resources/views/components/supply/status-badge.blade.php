@props(['status'])

@php
    $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $statusValue = $statusValue === '' ? 'unknown' : $statusValue;
    $label = str_replace('_', ' ', $statusValue);
@endphp

<span class="status-badge" data-status="{{ $statusValue }}">{{ $label }}</span>
