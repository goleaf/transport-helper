@extends('layouts.app')

@section('title')
Order Proposals
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Human approval queue</p>
        <h1>Order Proposals</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

<form class="filters" method="get" action="{{ route('supply.proposals.index') }}">
    <label>
        Status
        <select class="select select-bordered select-primary" name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected($statusFilter === $status->value)><x-supply.human-label :value="$status" /></option>
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
        Calculation date from
        <input class="input input-bordered input-primary" name="calculation_date_from" type="date" value="{{ $filters['calculation_date_from'] ?? '' }}">
    </label>

    <label>
        Calculation date to
        <input class="input input-bordered input-primary" name="calculation_date_to" type="date" value="{{ $filters['calculation_date_to'] ?? '' }}">
    </label>

    <label>
        Needs review
        <select class="select select-bordered select-primary" name="needs_review">
            <option value="">Any</option>
            <option value="1" @selected((string) ($filters['needs_review'] ?? '') === '1')>Yes</option>
        </select>
    </label>

    <div class="actions">
        <x-supply.button type="submit">Filter</x-supply.button>
        <x-supply.button :href="route('supply.proposals.index')" mode="outline" variant="neutral">Clear filters</x-supply.button>
    </div>
</form>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Proposal runs</p>
            <h2>Approval workload</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier</th>
                <th>Calculation</th>
                <th>Status</th>
                <th>Lines</th>
                <th>Needs review</th>
                <th>Approved</th>
                <th>Adjusted</th>
                <th>Rejected</th>
                <th>Recommended</th>
                <th>Approved quantity</th>
                <th>People</th>
                <th>Approved at</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($proposals as $proposal)
                <tr>
                    <td>{{ $proposal->id }}</td>
                    <td>{{ $proposal->supplier?->name }}</td>
                    <td>
                        <strong>{{ $proposal->calculationRun?->calculation_date?->toDateString() }}</strong>
                        <span>{{ $proposal->calculationRun?->formula_version }}</span>
                    </td>
                    <td>@include('supply.proposals.partials.status-badge', ['status' => $proposal->status])</td>
                    <td>{{ $proposal->total_lines ?: $proposal->items_count }}</td>
                    <td>{{ $proposal->needs_review_count }}</td>
                    <td>{{ $proposal->approved_count }}</td>
                    <td>{{ $proposal->adjusted_count }}</td>
                    <td>{{ $proposal->rejected_count }}</td>
                    <td>{{ number_format((float) ($proposal->total_recommended_quantity ?? 0), 3) }}</td>
                    <td>{{ number_format((float) ($proposal->total_approved_quantity ?? 0), 3) }}</td>
                    <td>
                        <strong>{{ $proposal->createdBy?->name ?? 'System' }}</strong>
                        <span>{{ $proposal->approvedBy?->name ?? 'Not approved' }}</span>
                    </td>
                    <td>{{ $proposal->approved_at?->toDateTimeString() ?? 'Not approved' }}</td>
                    <td><x-supply.table-action :href="route('supply.proposals.show', $proposal)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="14">No order proposals yet. Run calculation first.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $proposals->links() }}
</section>
@endsection
