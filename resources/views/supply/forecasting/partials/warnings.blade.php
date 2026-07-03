@props(['warnings' => []])

@if ($warnings !== [])
    <x-supply.alert tone="warning">
        <strong>Review warnings</strong>
        <ul class="structured-list">
            @forelse ($warnings as $warning)
                <li>{{ $warning }}</li>
            @empty
                <li>No warnings.</li>
            @endforelse
        </ul>
    </x-supply.alert>
@endif
