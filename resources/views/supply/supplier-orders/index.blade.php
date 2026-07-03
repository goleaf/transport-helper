@extends('layouts.app')

@section('title')
Supplier Orders
@endsection

@section('content')
<header>
    <h1>Supplier Orders</h1>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

<form method="get" action="{{ route('supply.supplier-orders.index') }}">
    <label>
        Status
        <select name="status">
            <option value="">Any</option>
            @foreach (['draft', 'email_prepared', 'approved', 'sent', 'confirmed', 'partially_confirmed', 'delayed', 'completed', 'cancelled', 'needs_review'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)><x-supply.human-label :value="$status" /></option>
            @endforeach
        </select>
    </label>
    <label>
        Supplier
        <select name="supplier_id">
            <option value="">Any</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </label>
    <label>
        Order date from
        <input type="date" name="order_date_from" value="{{ $filters['order_date_from'] ?? '' }}">
    </label>
    <label>
        Order date to
        <input type="date" name="order_date_to" value="{{ $filters['order_date_to'] ?? '' }}">
    </label>
    <button type="submit">Filter</button>
    <a href="{{ route('supply.supplier-orders.index') }}">Clear</a>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Order number</th>
            <th>Supplier</th>
            <th>Status</th>
            <th>Order date</th>
            <th>Items</th>
            <th>Total ordered quantity</th>
            <th>Email messages</th>
            <th>Sent at</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->supplier?->name }}</td>
                <td>@include('supply.supplier-orders.partials.status-badge', ['status' => $order->status])</td>
                <td>{{ $order->order_date?->toDateString() }}</td>
                <td>{{ $order->items_count }}</td>
                <td>{{ $order->items_sum_ordered_quantity ?? 0 }}</td>
                <td>{{ $order->email_messages_count }}</td>
                <td>{{ $order->sent_at?->toDateTimeString() }}</td>
                <td><x-supply.table-action :href="route('supply.supplier-orders.show', $order)" label="View" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="10">No supplier orders yet. Convert an approved order proposal first.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $orders->links() }}
@endsection
