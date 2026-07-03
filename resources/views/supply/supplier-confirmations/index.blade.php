@extends('layouts.app')

@section('title')
Supplier Confirmations
@endsection

@section('content')
<header>
    <h1>Supplier Confirmations</h1>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

<section>
    <form method="GET" action="{{ route('supply.supplier-confirmations.index') }}">
        <label>Status <input type="text" name="status" value="{{ $filters['status'] ?? '' }}"></label>
        <label>Supplier
            <select name="supplier_id">
                <option value="">Any</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Supplier order <input type="number" name="supplier_order_id" value="{{ $filters['supplier_order_id'] ?? '' }}"></label>
        <label>Source <input type="text" name="source_type" value="{{ $filters['source_type'] ?? '' }}"></label>
        <label>Date from <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
        <label>Date to <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
        <label><input type="checkbox" name="needs_review" value="1" @checked((bool) ($filters['needs_review'] ?? false))> Needs review</label>
        <button type="submit">Filter</button>
    </form>
</section>

<section>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier</th>
                <th>Supplier order</th>
                <th>Reference</th>
                <th>Status</th>
                <th>Confirmation date</th>
                <th>Ready date</th>
                <th>Expected arrival</th>
                <th>Source</th>
                <th>Discrepancy summary</th>
                <th>Applied by</th>
                <th>Applied at</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($confirmations as $confirmation)
                <tr>
                    <td>{{ $confirmation->id }}</td>
                    <td>{{ $confirmation->supplierOrder?->supplier?->name }}</td>
                    <td>{{ $confirmation->supplierOrder?->order_number }}</td>
                    <td>{{ $confirmation->supplier_reference }}</td>
                    <td>@include('supply.supplier-confirmations.partials.status-badge', ['status' => $confirmation->status])</td>
                    <td>{{ $confirmation->confirmation_date?->toDateString() }}</td>
                    <td>{{ $confirmation->ready_date?->toDateString() }}</td>
                    <td>{{ $confirmation->expected_arrival_date?->toDateString() }}</td>
                    <td>{{ $confirmation->source_type }}</td>
                    <td>{{ $confirmation->discrepancy_summary }}</td>
                    <td>{{ $confirmation->appliedBy?->name }}</td>
                    <td>{{ $confirmation->applied_at?->toDateTimeString() }}</td>
                    <td><x-supply.table-action :href="route('supply.supplier-confirmations.show', $confirmation)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No supplier confirmations.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $confirmations->links() }}
</section>
@endsection
