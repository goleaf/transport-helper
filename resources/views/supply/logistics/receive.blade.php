@extends('layouts.app')

@section('title')
Record Goods Receipt
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.logistics.show', $record) }}">Back to logistics record</a></p>
    <h1>Record Goods Receipt</h1>
    <p>{{ $order?->order_number }} - {{ $record->supplier?->name }}</p>
</header>

@if ($errors->any())
    <section>
        <h2>Errors</h2>
        <ul>
            @forelse ($errors->all() as $error)
                <li>{{ $error }}</li>
            @empty
                <li>No errors.</li>
            @endforelse
        </ul>
    </section>
@endif

<form method="post" action="{{ route('supply.logistics.receive.store', $record) }}">
    @csrf
    <label>Actual received date <input type="date" name="actual_received_date" value="{{ old('actual_received_date', now()->toDateString()) }}" required></label>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Ordered</th>
                <th>Confirmed</th>
                <th>Expected</th>
                <th>Current received</th>
                <th>Received</th>
                <th>Damaged</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order?->items ?? [] as $index => $item)
                <tr>
                    <td>{{ $item->product?->sku }}<input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}"></td>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->ordered_quantity }}</td>
                    <td>{{ $item->confirmed_quantity }}</td>
                    <td>{{ $item->expected_receipt_quantity }}</td>
                    <td>{{ $item->received_quantity }}</td>
                    <td><input type="number" step="0.001" name="items[{{ $index }}][received_quantity]" value="{{ old('items.'.$index.'.received_quantity', $item->expected_receipt_quantity) }}" required></td>
                    <td><input type="number" step="0.001" name="items[{{ $index }}][damaged_quantity]" value="{{ old('items.'.$index.'.damaged_quantity', 0) }}"></td>
                    <td><input type="text" name="items[{{ $index }}][notes]" value="{{ old('items.'.$index.'.notes') }}"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No order items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <label><input type="checkbox" name="complete_order" value="1" checked> Complete order</label>
    <label><input type="checkbox" name="confirm_mismatches" value="1" @checked(old('confirm_mismatches'))> Confirm mismatches</label>
    <label>Notes <textarea name="notes">{{ old('notes') }}</textarea></label>

    <p>If received quantities differ from expected quantities, the system will record discrepancies and may mark logistics/order as needs_review.</p>
    <button type="submit">Record receipt</button>
</form>
@endsection
