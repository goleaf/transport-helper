<div class="flex flex-wrap items-center gap-2" aria-label="Environment badges">
    @forelse ($badges as $badge)
        <x-supply.badge tone="{{ $badge['tone'] }}" title="{{ $badge['description'] }}">
            {{ $badge['label'] }}
        </x-supply.badge>
    @empty
        <x-supply.badge tone="success">LOCAL MODE</x-supply.badge>
    @endforelse
</div>
