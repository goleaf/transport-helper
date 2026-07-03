@props(['item'])

<article class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-supply.risk-badge :risk="$item['priority']" />
            <span class="text-sm text-slate-500">{{ $item['age'] }}</span>
        </div>
        <h3 class="card-title text-base">{{ $item['title'] }}</h3>
        <p class="text-sm font-medium text-slate-700">{{ $item['object_label'] }}</p>
        <p class="text-sm text-slate-600">{{ $item['reason'] }}</p>
        <x-supply.button :href="$item['url']" variant="primary" size="sm">Review</x-supply.button>
    </div>
</article>
