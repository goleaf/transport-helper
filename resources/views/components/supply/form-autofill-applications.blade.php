<section>
    <h2>Apply as supplier confirmation</h2>
    @if ($canShowSupplierConfirmationForm)
        <form method="POST" action="{{ route('supply.form-autofill-runs.apply-supplier-confirmation', $run) }}">
            @csrf
            <label>Supplier order ID <input class="input input-bordered input-primary" type="number" name="supplier_order_id" value="{{ $run->emailMessage?->related_supplier_order_id }}"></label>
            <label><input class="checkbox checkbox-primary" type="checkbox" name="update_inbound" value="1" checked> Update inbound</label>
            <label><input class="checkbox checkbox-primary" type="checkbox" name="update_logistics" value="1" checked> Update logistics</label>
            <label><input class="checkbox checkbox-primary" type="checkbox" name="allow_missing_items" value="1"> Allow missing items</label>
            <label><input class="checkbox checkbox-primary" type="checkbox" name="allow_over_confirmation" value="1"> Allow over confirmation</label>
            <label><input class="checkbox checkbox-primary" type="checkbox" name="confirm_apply" value="1" required> Confirm apply</label>
            <x-supply.button type="submit">Apply supplier confirmation</x-supply.button>
        </form>
    @elseif (! $isValidated)
        <p>Validate autofill run before applying it.</p>
    @else
        <p>This form autofill run is not compatible with supplier confirmation application.</p>
    @endif
</section>

<section>
    <h2>Apply as carrier quote</h2>
    @if ($canShowCarrierQuoteForm)
        <form method="POST" action="{{ route('supply.form-autofill-runs.apply-carrier-quote', $run) }}">
            @csrf
            <label>Supplier order ID <input class="input input-bordered input-primary" type="number" name="supplier_order_id" value="{{ $run->emailMessage?->related_supplier_order_id }}"></label>
            <label><input class="checkbox checkbox-primary" type="checkbox" name="allow_missing_delivery_date" value="1"> Allow missing delivery date</label>
            <label><input class="checkbox checkbox-primary" type="checkbox" name="allow_zero_price" value="1"> Allow zero price</label>
            <label><input class="checkbox checkbox-primary" type="checkbox" name="confirm_apply" value="1" required> Confirm apply</label>
            <x-supply.button type="submit">Create carrier quote candidate</x-supply.button>
        </form>
    @elseif (! $isValidated)
        <p>Validate autofill run before applying it.</p>
    @else
        <p>This form autofill run is not compatible with carrier quote application.</p>
    @endif
</section>
