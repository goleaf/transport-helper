@props(['label', 'description', 'url' => null, 'enabled' => true, 'disabledReason' => '', 'severity' => 'normal'])

<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="{{ $label }}">
    <div class="card-body">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="card-title text-base">{{ $label }}</h2>
            <x-supply.risk-badge :risk="$severity" />
        </div>
        <p class="text-sm text-slate-600">{{ $description }}</p>
        @if ($enabled)
            <x-supply.button :href="$url" variant="primary" size="sm">Continue</x-supply.button>
        @else
            <x-supply.alert tone="warning">{{ $disabledReason }}</x-supply.alert>
        @endif
    </div>
</section>
