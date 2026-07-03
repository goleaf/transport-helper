@php
    $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = str_replace('_', ' ', $statusValue);
@endphp

<span style="display:inline-block; border:1px solid #a9b4c0; padding:2px 8px; font-size:12px; text-transform:capitalize;">
    {{ $label }}
</span>
