<div class="rounded-lg border border-gray-200 bg-white p-4">
    <h2 class="text-base font-semibold text-gray-900">Approval</h2>
    <div class="mt-4 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('supply.integrations.submit-approval', $connection) }}">
            @csrf
            <x-supply.button type="submit" mode="outline" variant="neutral">Submit approval</x-supply.button>
        </form>
        <form method="POST" action="{{ route('supply.integrations.approve', $connection) }}">
            @csrf
            <x-supply.button type="submit">Approve</x-supply.button>
        </form>
        <form method="POST" action="{{ route('supply.integrations.activate', $connection) }}">
            @csrf
            <label class="mr-3 text-sm text-gray-700"><input type="checkbox" name="override_activation" value="1" class="checkbox checkbox-primary"> Override test</label>
            <x-supply.button type="submit" mode="outline" variant="neutral">Activate</x-supply.button>
        </form>
        <form method="POST" action="{{ route('supply.integrations.disable', $connection) }}">
            @csrf
            <input type="hidden" name="reason" value="Disabled from integration detail page.">
            <x-supply.button type="submit" mode="outline" variant="neutral">Disable</x-supply.button>
        </form>
    </div>
</div>
