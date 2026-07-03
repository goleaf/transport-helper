<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="Formula explanation">
    <div class="card-body">
        <h2 class="card-title text-base">Formula explanation</h2>
        <p class="text-sm text-slate-600">Deterministic calculation steps. AI does not calculate order quantities.</p>

        @if ($hasSteps)
            <ol class="space-y-3">
                @forelse ($steps as $step)
                    <li class="rounded-lg border border-base-300 bg-base-200 p-3">
                        <p class="font-semibold">{{ $step['name'] }}</p>
                        <p class="text-sm text-slate-700">{{ $step['formula'] }}</p>
                        <p class="text-sm text-slate-600">{{ $step['calculation'] }}</p>
                        <x-supply.badge tone="info">{{ $step['value'] }}</x-supply.badge>
                    </li>
                @empty
                    <li>No formula steps.</li>
                @endforelse
            </ol>
        @else
            <x-supply.empty-state title="No formula steps" message="The calculation explanation is not available for this item." />
        @endif

        <div class="rounded-lg border border-base-300 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Final result</p>
            <p class="text-lg font-semibold">{{ $finalResult }}</p>
        </div>
    </div>
</section>
