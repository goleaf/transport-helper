@props(['title' => 'Decision', 'reason' => 'Reason is required for changes.'])

<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="{{ $title }}">
    <div class="card-body">
        <h2 class="card-title text-base">{{ $title }}</h2>
        <x-supply.alert tone="info">{{ $reason }}</x-supply.alert>
        {{ $slot }}
    </div>
</section>
