<div class="rounded-lg border border-gray-200 bg-white p-4">
    <h2 class="text-base font-semibold text-gray-900">Connection Test</h2>
    <p class="mt-1 text-sm text-gray-600">Dry-run is the default and does not contact external providers.</p>
    <div class="mt-4 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('supply.integrations.test', $connection) }}">
            @csrf
            <input type="hidden" name="dry_run" value="1">
            <x-supply.button type="submit">Test dry-run</x-supply.button>
        </form>
        <form method="POST" action="{{ route('supply.integrations.test', $connection) }}">
            @csrf
            <label class="mr-3 text-sm text-gray-700"><input type="checkbox" name="allow_real_call" value="1" class="checkbox checkbox-primary"> I understand this may contact an external provider.</label>
            <x-supply.button type="submit" mode="outline" variant="neutral">Test real connection</x-supply.button>
        </form>
    </div>
</div>
