@extends('layouts.app')

@section('title')
Procurement Exceptions
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Procurement controls</p>
        <h1>Procurement Exceptions</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Exception request</p>
            <h2>Create controlled exception</h2>
        </div>
    </div>

    <form method="POST" action="{{ route('supply.procurement.exceptions.store') }}" class="form-grid">
        @csrf

        <label>
            <span>Subject type</span>
            <select class="select select-bordered" name="exceptable_type" required>
                <option value="proposal" @selected(old('exceptable_type') === 'proposal')>Order proposal</option>
                <option value="supplier_order" @selected(old('exceptable_type') === 'supplier_order')>Supplier order</option>
                <option value="scenario" @selected(old('exceptable_type') === 'scenario')>Calculation scenario</option>
            </select>
        </label>

        <label>
            <span>Subject ID</span>
            <input class="input input-bordered" type="number" min="1" name="exceptable_id" value="{{ old('exceptable_id') }}" required>
        </label>

        <label>
            <span>Exception type</span>
            <select class="select select-bordered" name="exception_type" required>
                <option value="budget_overrun">Budget overrun</option>
                <option value="missing_price">Missing price</option>
                <option value="supplier_minimum_not_met">Supplier minimum not met</option>
                <option value="supplier_maximum_exceeded">Supplier maximum exceeded</option>
                <option value="order_frequency_violation">Order frequency violation</option>
                <option value="urgent_purchase">Urgent purchase</option>
                <option value="manual_override">Manual override</option>
                <option value="other">Other</option>
            </select>
        </label>

        <label>
            <span>Reason</span>
            <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
        </label>

        <div class="form-actions">
            <x-supply.button type="submit">Request exception</x-supply.button>
        </div>
    </form>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Exceptions</p>
            <h2>Pending and decided exceptions</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Status</th>
                <th>Type</th>
                <th>Subject</th>
                <th>Requested by</th>
                <th>Approved by</th>
                <th>Reason</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($exceptions as $exception)
                <tr>
                    <td><x-supply.status-badge :status="$exception->status" /></td>
                    <td><x-supply.human-label :value="$exception->exception_type" /></td>
                    <td>
                        <strong>{{ class_basename($exception->exceptable_type) }}</strong>
                        <span>#{{ $exception->exceptable_id }}</span>
                    </td>
                    <td>{{ $exception->requestedBy?->name ?? 'System' }}</td>
                    <td>{{ $exception->approvedBy?->name ?? 'Not approved' }}</td>
                    <td>{{ $exception->reason }}</td>
                    <td><x-supply.table-action :href="route('supply.procurement.exceptions.show', $exception)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No procurement exceptions.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $exceptions->links() }}
</section>
@endsection
