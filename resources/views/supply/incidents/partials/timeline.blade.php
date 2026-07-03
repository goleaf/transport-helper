@props(['events'])

<ol class="timeline timeline-vertical">
    @forelse ($events as $event)
        <li>
            <div class="timeline-middle">●</div>
            <div class="timeline-end mb-6">
                <time class="text-sm opacity-70">{{ $event->created_at?->format('Y-m-d H:i') }}</time>
                <h3 class="font-semibold">{{ $event->event_type }}</h3>
                <p class="text-sm opacity-80">By {{ $event->createdBy?->name ?? 'System' }}</p>
            </div>
            <hr>
        </li>
    @empty
        <li>No incident history yet.</li>
    @endforelse
</ol>
