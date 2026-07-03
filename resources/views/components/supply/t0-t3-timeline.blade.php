<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="T0 T1 T2 T3 timeline">
    <div class="card-body">
        <h2 class="card-title text-base">T0/T1/T2/T3 timeline</h2>
        <ol class="grid gap-3 md:grid-cols-4">
            @forelse ($items as $item)
                <li class="rounded-lg border border-base-300 bg-base-200 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item['label'] }}</p>
                    <p class="font-semibold">{{ $item['title'] }}</p>
                    <p class="text-sm text-slate-600">{{ $item['value'] }}</p>
                </li>
            @empty
                <li>No timeline values.</li>
            @endforelse
        </ol>
        <x-supply.alert tone="info">
            Safety stock covers only T2-T3 and must not duplicate T1-T2.
        </x-supply.alert>
    </div>
</section>
