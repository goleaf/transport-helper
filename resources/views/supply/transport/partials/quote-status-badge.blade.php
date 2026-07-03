@php($value = $status instanceof \BackedEnum ? $status->value : (string) $status)
<span>{{ str($value)->replace('_', ' ')->title() }}</span>
