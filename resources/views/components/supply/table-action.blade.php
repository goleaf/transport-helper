<form method="GET" action="{{ $href }}" {{ $attributes->class('table-action-form') }}>
    <x-supply.button type="submit" size="sm" mode="outline" class="table-action-button">{{ $label }}</x-supply.button>
</form>
