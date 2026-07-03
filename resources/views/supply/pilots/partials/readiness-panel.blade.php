<div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-base font-semibold text-gray-900">Readiness</h2>
        <form method="POST" action="{{ route('supply.pilots.readiness-check', $pilot) }}">
            @csrf
            <x-supply.button type="submit">Run readiness check</x-supply.button>
        </form>
    </div>

    <div class="mt-4 space-y-2">
        @forelse (($pilot->readiness_result_json['data_quality']['checks'] ?? []) as $check)
            <div class="flex items-start justify-between gap-3 rounded border border-gray-100 p-2 text-sm">
                <span>{{ $check['message'] }}</span>
                <x-supply.badge>{{ $check['status'] }}</x-supply.badge>
            </div>
        @empty
            <p class="text-sm text-gray-500">Readiness has not been checked yet.</p>
        @endforelse
    </div>
</div>
