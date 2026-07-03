@php
    $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
@endphp

<span>{{ $statusValue }}</span>
