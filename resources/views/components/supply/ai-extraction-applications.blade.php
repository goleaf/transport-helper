<section>
    <h2>Apply as supplier confirmation</h2>
    @if ($canShowSupplierConfirmationForm)
        <form method="POST" action="{{ route('supply.ai-extractions.apply-supplier-confirmation', $extraction) }}">
            @csrf
            <label>Supplier order ID <input type="number" name="supplier_order_id" value="{{ $extraction->emailMessage?->related_supplier_order_id }}"></label>
            <label><input type="checkbox" name="update_inbound" value="1" checked> Update inbound</label>
            <label><input type="checkbox" name="update_logistics" value="1" checked> Update logistics</label>
            <label><input type="checkbox" name="allow_missing_items" value="1"> Allow missing items</label>
            <label><input type="checkbox" name="allow_over_confirmation" value="1"> Allow over confirmation</label>
            <label><input type="checkbox" name="confirm_apply" value="1" required> Confirm apply</label>
            <button type="submit">Apply supplier confirmation</button>
        </form>
    @elseif (! $isAccepted)
        <p>Accept extraction before applying it.</p>
    @else
        <p>This extraction is not ready to apply as supplier confirmation.</p>
    @endif
</section>

<section>
    <h2>Apply as carrier quote</h2>
    @if ($canShowCarrierQuoteForm)
        <form method="POST" action="{{ route('supply.ai-extractions.apply-carrier-quote', $extraction) }}">
            @csrf
            <label>Supplier order ID <input type="number" name="supplier_order_id" value="{{ $extraction->emailMessage?->related_supplier_order_id }}"></label>
            <label><input type="checkbox" name="allow_missing_delivery_date" value="1"> Allow missing delivery date</label>
            <label><input type="checkbox" name="allow_zero_price" value="1"> Allow zero price</label>
            <label><input type="checkbox" name="confirm_apply" value="1" required> Confirm apply</label>
            <button type="submit">Create carrier quote candidate</button>
        </form>
    @elseif (! $isAccepted)
        <p>Accept extraction first.</p>
    @else
        <p>This extraction is not compatible with carrier quote application.</p>
    @endif
</section>
