@props(['extraction', 'canAccept', 'canReject', 'canRequestHumanReview'])

<form method="post" action="{{ route('supply.ai-extractions.review', $extraction) }}">
    @csrf
    <label>
        Note
        <textarea class="textarea textarea-bordered textarea-primary" name="note" rows="3"></textarea>
    </label>

    @if ($canAccept)
        <x-supply.button type="submit" name="decision" value="accept">Accept</x-supply.button>
    @endif

    @if ($canReject)
        <x-supply.button type="submit" name="decision" value="reject">Reject</x-supply.button>
    @endif

    @if ($canRequestHumanReview)
        <x-supply.button type="submit" name="decision" value="needs_review">Mark needs review</x-supply.button>
    @endif
</form>
