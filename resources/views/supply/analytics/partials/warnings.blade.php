@if (($warnings ?? []) !== [])
    <section class="alert alert-warning">
        <div>
            <h2>Warnings</h2>
            <ul>
                @forelse ($warnings as $warning)
                    <li>{{ $warning }}</li>
                @empty
                    <li>No warnings.</li>
                @endforelse
            </ul>
        </div>
    </section>
@endif
