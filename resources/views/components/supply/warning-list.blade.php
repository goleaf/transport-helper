@props(['warnings' => [], 'title' => 'Warnings'])

<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="{{ $title }}">
    <div class="card-body">
        <h2 class="card-title text-base">{{ $title }}</h2>
        <ul class="space-y-2">
            @forelse ($warnings as $warning)
                <li>
                    <x-supply.alert tone="warning">{{ $warning }}</x-supply.alert>
                </li>
            @empty
                <li>No warnings.</li>
            @endforelse
        </ul>
    </div>
</section>
