<form method="post" action="{{ route('supply.form-autofill-runs.fields.accept', [$run, $field]) }}">
    @csrf
    <x-supply.button type="submit">Accept</x-supply.button>
</form>
<form method="post" action="{{ route('supply.form-autofill-runs.fields.update', [$run, $field]) }}">
    @csrf
    <input class="input input-bordered input-primary" name="final_value" value="{{ $finalValue }}">
    <input class="input input-bordered input-primary" name="reason" placeholder="Reason">
    <x-supply.button type="submit">Edit</x-supply.button>
</form>
<form method="post" action="{{ route('supply.form-autofill-runs.fields.reject', [$run, $field]) }}">
    @csrf
    <input class="input input-bordered input-primary" name="reason" placeholder="Reason">
    <x-supply.button type="submit">Reject</x-supply.button>
</form>
