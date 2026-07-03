@props(['title', 'value', 'subtitle' => '', 'tone' => 'neutral', 'url' => null])

<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="{{ $title }}">
    <div class="card-body">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-slate-600">{{ $title }}</p>
                <p class="text-3xl font-semibold text-slate-950">{{ $value }}</p>
            </div>
            <x-supply.badge tone="{{ $tone }}">{{ $tone }}</x-supply.badge>
        </div>
        <p class="text-sm text-slate-600">{{ $subtitle }}</p>
        @if ($url)
            <x-supply.button :href="$url" variant="neutral" mode="outline" size="sm">Open</x-supply.button>
        @endif
    </div>
</section>
