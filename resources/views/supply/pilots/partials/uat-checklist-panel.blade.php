<div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">UAT Checklist</h2>
            <p class="text-sm text-gray-600">Critical blockers: {{ $evaluation['pending_critical_count'] ?? 0 }}</p>
        </div>
        <x-supply.button :href="route('supply.pilots.uat', $pilot)" mode="outline" variant="neutral">Open checklist</x-supply.button>
    </div>

    <form method="POST" action="{{ route('supply.pilots.uat.update', $pilot) }}" class="mt-4 space-y-3">
        @csrf
        @foreach ($checklist as $index => $item)
            <div class="grid gap-2 rounded border border-gray-100 p-3 md:grid-cols-5">
                <input type="hidden" name="items[{{ $index }}][key]" value="{{ $item['key'] }}">
                <div class="md:col-span-2">
                    <div class="font-medium text-gray-900">{{ $item['label'] }}</div>
                    <div class="text-xs text-gray-500">{{ $item['section'] }} · {{ $item['owner'] }} @if($item['critical']) · critical @endif</div>
                </div>
                <select name="items[{{ $index }}][status]" class="select select-bordered select-primary">
                    @foreach (['pending','passed','failed','blocked','not_applicable'] as $status)
                        <option value="{{ $status }}" @selected(($item['status'] ?? 'pending') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <input name="items[{{ $index }}][note]" value="{{ $item['note'] ?? '' }}" placeholder="Note" class="input input-bordered input-primary">
                <input name="items[{{ $index }}][evidence]" value="{{ $item['evidence'] ?? '' }}" placeholder="Evidence" class="input input-bordered input-primary">
            </div>
        @endforeach
        <x-supply.button type="submit">Save checklist</x-supply.button>
    </form>
</div>
