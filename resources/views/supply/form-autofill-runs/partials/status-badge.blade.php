@php($value = $status instanceof \BackedEnum ? $status->value : (string) $status)
<span>{{ $value }}</span>
