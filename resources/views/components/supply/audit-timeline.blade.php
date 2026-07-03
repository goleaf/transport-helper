@props(['logs' => []])

<section class="card bg-base-100 border border-base-300 shadow-sm" aria-label="Audit timeline">
    <div class="card-body">
        <h2 class="card-title text-base">Audit timeline</h2>
        <ol class="space-y-3">
            @forelse ($logs as $log)
                <li class="rounded-lg border border-base-300 p-3">
                    <p class="font-semibold">{{ $log['event_type'] }}</p>
                    <p class="text-sm text-slate-600">{{ $log['user'] }}</p>
                    <p class="text-sm text-slate-500">{{ $log['created_at'] }}</p>
                </li>
            @empty
                <li>No audit entries yet.</li>
            @endforelse
        </ol>
    </div>
</section>
