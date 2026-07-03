<x-supply.alert tone="{{ $blocking ? 'error' : 'warning' }}">
    <div>
        <p class="font-semibold">Requires human review</p>
        <p>{{ $reason }}</p>
        <p>{{ $action }}</p>
    </div>
</x-supply.alert>
