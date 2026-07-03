@extends('layouts.app')

@section('title')
Create Manual Confirmation
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.supplier-orders.show', $order) }}">Back to supplier order</a></p>
    <h1>Create Manual Confirmation</h1>
    <p>Applying confirmation updates supplier order status, confirmed quantities, inbound and logistics records. Mismatches will be stored and shown for review.</p>
</header>

@if ($errors->any())
    <section>
        <h2>Errors</h2>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </section>
@endif

<section>
    <h2>Supplier order</h2>
    <dl>
        <dt>Order number</dt>
        <dd>{{ $order->order_number }}</dd>
        <dt>Supplier</dt>
        <dd>{{ $order->supplier?->name }}</dd>
        <dt>Status</dt>
        <dd>{{ $order->status instanceof \BackedEnum ? $order->status->value : $order->status }}</dd>
    </dl>
</section>

<form method="POST" action="{{ route('supply.supplier-orders.confirmations.store', $order) }}">
    @csrf
    <fieldset>
        <legend>Header</legend>
        <label>Supplier reference <input type="text" name="supplier_reference" value="{{ old('supplier_reference') }}"></label>
        <label>Confirmation date <input type="date" name="confirmation_date" value="{{ old('confirmation_date') }}"></label>
        <label>Ready date <input type="date" name="ready_date" value="{{ old('ready_date') }}"></label>
        <label>Shipping date <input type="date" name="shipping_date" value="{{ old('shipping_date') }}"></label>
        <label>Expected arrival date <input type="date" name="expected_arrival_date" value="{{ old('expected_arrival_date') }}"></label>
    </fieldset>

    <fieldset>
        <legend>Items</legend>
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Ordered</th>
                    <th>Confirmed</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    <tr>
                        <td>
                            {{ $item->product?->sku }}
                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                            <input type="hidden" name="items[{{ $index }}][sku]" value="{{ $item->product?->sku }}">
                        </td>
                        <td>{{ $item->product?->name }}</td>
                        <td>{{ $item->ordered_quantity }}</td>
                        <td><input type="number" step="0.001" min="0" name="items[{{ $index }}][confirmed_quantity]" value="{{ old("items.{$index}.confirmed_quantity", $item->ordered_quantity) }}"></td>
                        <td><input type="text" name="items[{{ $index }}][notes]" value="{{ old("items.{$index}.notes") }}"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </fieldset>

    <fieldset>
        <legend>Options</legend>
        <label><input type="checkbox" name="update_inbound" value="1" checked> Update inbound</label>
        <label><input type="checkbox" name="update_logistics" value="1" checked> Update logistics</label>
        <label><input type="checkbox" name="allow_missing_items" value="1"> Allow missing items</label>
        <label><input type="checkbox" name="allow_over_confirmation" value="1"> Allow over confirmation</label>
    </fieldset>

    <button type="submit">Apply supplier confirmation</button>
</form>
@endsection
