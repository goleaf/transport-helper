<form method="post" action="{{ route('supply.transport.quotes.select', $quote) }}">
    @csrf
    <input type="hidden" name="confirmation" value="1">
    <input type="hidden" name="confirm_selection" value="1">
    @if ($quote->needs_review)
        <label><input type="checkbox" name="override_needs_review" value="1"> Override needs review</label>
        <label>Reason <input name="override_reason"></label>
    @endif
    <label><input type="checkbox" name="replace_existing" value="1"> Replace existing</label>
    <label><input type="checkbox" name="reject_others" value="1"> Reject others</label>
    <button type="submit">Select carrier</button>
</form>
<form method="post" action="{{ route('supply.transport.quotes.reject', $quote) }}">
    @csrf
    <label>Reason <input name="rejection_reason"></label>
    <button type="submit">Reject quote</button>
</form>
