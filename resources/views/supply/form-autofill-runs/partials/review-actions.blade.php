<form method="post" action="{{ route('supply.form-autofill-runs.fields.accept', [$run, $field]) }}">
    @csrf
    <button type="submit">Accept</button>
</form>
<form method="post" action="{{ route('supply.form-autofill-runs.fields.update', [$run, $field]) }}">
    @csrf
    <input name="final_value" value="{{ $finalValue }}">
    <input name="reason" placeholder="Reason">
    <button type="submit">Edit</button>
</form>
<form method="post" action="{{ route('supply.form-autofill-runs.fields.reject', [$run, $field]) }}">
    @csrf
    <input name="reason" placeholder="Reason">
    <button type="submit">Reject</button>
</form>
