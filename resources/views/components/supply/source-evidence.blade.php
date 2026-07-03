<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="Source evidence for {{ $field }}">
    <div class="card-body">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="card-title text-base">{{ $field }}</h2>
                <p class="text-sm text-slate-600">AI suggestions are not final values.</p>
            </div>
            <x-supply.ai-confidence-badge :confidence="$confidence" />
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-violet-200 bg-violet-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Suggested</p>
                <p class="font-semibold text-slate-900">{{ $suggested }}</p>
            </div>
            <div class="rounded-lg border border-sky-200 bg-sky-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Normalized</p>
                <p class="font-semibold text-slate-900">{{ $normalized }}</p>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Final value</p>
                <p class="font-semibold text-slate-900">{{ $final }}</p>
            </div>
        </div>

        <blockquote class="rounded-lg border border-base-300 bg-base-200 p-3 text-sm text-slate-700">
            {{ $source }}
        </blockquote>

        @if ($reviewReason)
            <x-supply.human-review-banner :reason="$reviewReason" action="Review the source evidence before applying this value." />
        @endif
    </div>
</section>
