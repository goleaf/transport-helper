@extends('layouts.app')

@section('title')
Supplier Orders
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Approved procurement workflow</p>
        <h1>Supplier Orders</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<form class="filters" method="get" action="{{ route('supply.supplier-orders.index') }}">
    <label>
        Status
        <select class="select select-bordered select-primary" name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)><x-supply.human-label :value="$status" /></option>
            @endforeach
        </select>
    </label>

    <label>
        Supplier
        <select class="select select-bordered select-primary" name="supplier_id">
            <option value="">All suppliers</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </label>

    <label>
        Order date from
        <input class="input input-bordered input-primary" type="date" name="order_date_from" value="{{ $filters['order_date_from'] ?? '' }}">
    </label>

    <label>
        Order date to
        <input class="input input-bordered input-primary" type="date" name="order_date_to" value="{{ $filters['order_date_to'] ?? '' }}">
    </label>

    <div class="actions">
        <x-supply.button type="submit">Filter</x-supply.button>
        <x-supply.button :href="route('supply.supplier-orders.index')" mode="outline" variant="neutral">Clear filters</x-supply.button>
    </div>
</form>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Supplier order control</p>
            <h2>Order pipeline</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Order number</th>
                <th>Supplier</th>
                <th>Status</th>
                <th>Order date</th>
                <th>Items</th>
                <th>Ordered quantity</th>
                <th>Confirmations</th>
                <th>Inbound</th>
                <th>Quotes</th>
                <th>Logistics</th>
                <th>Email</th>
                <th>Sent at</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>
                        <strong>{{ $order->order_number }}</strong>
                        <span>Supplier order #{{ $order->id }}</span>
                    </td>
                    <td>{{ $order->supplier?->name }}</td>
                    <td>@include('supply.supplier-orders.partials.status-badge', ['status' => $order->status])</td>
                    <td>{{ $order->order_date?->toDateString() ?? 'Not set' }}</td>
                    <td>{{ $order->items_count }}</td>
                    <td>{{ number_format((float) ($order->items_sum_ordered_quantity ?? 0), 3) }}</td>
                    <td>{{ $order->confirmations_count }}</td>
                    <td>{{ $order->inbound_orders_count }}</td>
                    <td>{{ $order->carrier_quotes_count }}</td>
                    <td>{{ $order->logistics_records_count }}</td>
                    <td>{{ $order->email_messages_count }}</td>
                    <td>{{ $order->sent_at?->toDateTimeString() ?? 'Not sent' }}</td>
                    <td><x-supply.table-action :href="route('supply.supplier-orders.show', $order)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No supplier orders yet. Convert an approved order proposal first.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $orders->links() }}
</section>
@endsection
