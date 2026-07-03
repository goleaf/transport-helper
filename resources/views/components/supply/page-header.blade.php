@props(['title', 'subtitle' => '', 'status' => null, 'backUrl' => null])

<header class="flex flex-wrap items-start justify-between gap-4">
    <div>
        @if ($backUrl)
            <x-supply.button :href="$backUrl" variant="neutral" mode="ghost" size="sm">Back</x-supply.button>
        @endif
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-slate-600">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @if ($status)
            <x-supply.status-badge :status="$status" />
        @endif
        {{ $actions ?? '' }}
    </div>
</header>
