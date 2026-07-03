@if ($isConverted)
    <p>Proposal is converted to supplier order. Item decisions are closed.</p>
@else
    @if ($canApproveItem)
        <form method="post" action="{{ route('supply.proposals.items.approve', [$proposal, $item]) }}">
            @csrf
            @if ($item->requires_human_review)
                <label for="review_note">Review note</label>
                <textarea id="review_note" name="review_note">{{ old('review_note') }}</textarea>
                <label>
                    <input name="confirmed_review" type="checkbox" value="1" @checked(old('confirmed_review'))>
                    I reviewed this human-review line
                </label>
            @endif
            <button type="submit">Approve</button>
        </form>
    @endif

    @if ($canAdjustItem)
        <form method="post" action="{{ route('supply.proposals.items.adjust', [$proposal, $item]) }}">
            @csrf
            <label for="quantity">Quantity</label>
            <input id="quantity" name="quantity" type="number" min="0" step="0.001" value="{{ old('quantity', $item->approved_quantity ?? $item->recommended_quantity) }}">

            <label for="adjust_reason">Reason</label>
            <textarea id="adjust_reason" name="reason" required>{{ old('reason') }}</textarea>

            <button type="submit">Adjust</button>
        </form>
    @endif

    @if ($canRejectItem)
        <form method="post" action="{{ route('supply.proposals.items.reject', [$proposal, $item]) }}">
            @csrf
            <label for="reject_reason">Reason</label>
            <textarea id="reject_reason" name="reason" required>{{ old('reason') }}</textarea>

            <button type="submit">Reject</button>
        </form>
    @endif
@endif
