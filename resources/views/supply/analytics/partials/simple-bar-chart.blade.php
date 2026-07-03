<div>
    @forelse (($items ?? []) as $item)
        <div>
            <span>{{ $item['label'] ?? $item['name'] ?? 'Metric' }}</span>
            <div style="height: 0.75rem; width: {{ min(100, (float) ($item['value'] ?? 0)) }}%; background: #2563eb;"></div>
        </div>
    @empty
        <p>No chart data available.</p>
    @endforelse
</div>
