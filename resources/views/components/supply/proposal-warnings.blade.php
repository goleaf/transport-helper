{{ $label }}
@if ($hasWarnings)
    <x-supply.structured-value :value="$firstWarning" />
@endif
