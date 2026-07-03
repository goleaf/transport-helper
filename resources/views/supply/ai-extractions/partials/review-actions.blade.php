@props(['extraction', 'canAccept', 'canReject', 'canRequestHumanReview'])

<form method="post" action="{{ route('supply.ai-extractions.review', $extraction) }}">
    @csrf
    <label>
        Note
        <textarea name="note" rows="3"></textarea>
    </label>

    @if ($canAccept)
        <button type="submit" name="decision" value="accept">Accept</button>
    @endif

    @if ($canReject)
        <button type="submit" name="decision" value="reject">Reject</button>
    @endif

    @if ($canRequestHumanReview)
        <button type="submit" name="decision" value="needs_review">Mark needs review</button>
    @endif
</form>
