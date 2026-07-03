<div class="rounded-lg border border-gray-200 bg-white p-4">
    <h2 class="text-base font-semibold text-gray-900">Masked Config</h2>
    <dl class="mt-3 grid gap-3 md:grid-cols-2">
        @forelse ($maskedConfigLines as $line)
            <div>
                <dt class="text-xs uppercase text-gray-500">{{ $line['key'] }}</dt>
                <dd class="text-sm text-gray-900">{{ $line['value'] }}</dd>
            </div>
        @empty
            <div class="text-sm text-gray-500">No config values saved.</div>
        @endforelse
    </dl>
</div>
