@if ($isConverted)
    <p>Proposal is converted to supplier order. Item decisions are closed.</p>
@else
    @if ($canApproveItem)
        <form method="post" action="{{ route('supply.proposals.items.approve', [$proposal, $item]) }}">
            @csrf
            @if ($item->requires_human_review)
                <label for="review_note">Review note</label>
                <textarea class="textarea textarea-bordered textarea-primary" id="review_note" name="review_note">{{ old('review_note') }}</textarea>
                <label>
                    <input class="checkbox checkbox-primary" name="confirmed_review" type="checkbox" value="1" @checked(old('confirmed_review'))>
                    I reviewed this human-review line
                </label>
            @endif
            <x-supply.button type="submit">Approve</x-supply.button>
        </form>
    @endif

    @if ($canAdjustItem)
        <form method="post" action="{{ route('supply.proposals.items.adjust', [$proposal, $item]) }}">
            @csrf
            <label for="quantity">Quantity</label>
            <input class="input input-bordered input-primary" id="quantity" name="quantity" type="number" min="0" step="0.001" value="{{ old('quantity', $item->approved_quantity ?? $item->recommended_quantity) }}">

            <label for="adjust_reason">Reason</label>
            <textarea class="textarea textarea-bordered textarea-primary" id="adjust_reason" name="reason" required>{{ old('reason') }}</textarea>

            <x-supply.button type="submit">Adjust</x-supply.button>
        </form>
    @endif

    @if ($canRejectItem)
        <form method="post" action="{{ route('supply.proposals.items.reject', [$proposal, $item]) }}">
            @csrf
            <label for="reject_reason">Reason</label>
            <textarea class="textarea textarea-bordered textarea-primary" id="reject_reason" name="reason" required>{{ old('reason') }}</textarea>

            <x-supply.button type="submit">Reject</x-supply.button>
        </form>
    @endif
@endif
