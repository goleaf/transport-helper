<div class="rounded-lg border border-gray-200 bg-white p-4">
    <h2 class="text-base font-semibold text-gray-900">Approval</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-3">
        <form method="POST" action="{{ route('supply.pilots.approve-uat', $pilot) }}" class="space-y-2">
            @csrf
            <textarea name="note" rows="2" class="textarea textarea-bordered textarea-primary w-full" placeholder="UAT approval note"></textarea>
            <x-supply.button type="submit">Approve UAT</x-supply.button>
        </form>
        <form method="POST" action="{{ route('supply.pilots.approve-live', $pilot) }}" class="space-y-2">
            @csrf
            <textarea name="note" rows="2" class="textarea textarea-bordered textarea-primary w-full" placeholder="Live approval note"></textarea>
            <x-supply.button type="submit" mode="outline" variant="neutral">Approve live</x-supply.button>
        </form>
        <form method="POST" action="{{ route('supply.pilots.block', $pilot) }}" class="space-y-2">
            @csrf
            <textarea name="note" rows="2" class="textarea textarea-bordered textarea-primary w-full" placeholder="Block reason"></textarea>
            <x-supply.button type="submit" mode="outline" variant="neutral">Block</x-supply.button>
        </form>
    </div>
</div>
