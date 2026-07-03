@extends('layouts.app')

@section('title')
Procurement Budget
@endsection

@section('content')
<x-supply.page-header :title="$budget->name" subtitle="Budget period, lines and scope." :status="$budget->status" :back-url="route('supply.procurement.budgets.index')" />

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <div class="button-row">
        <x-supply.button :href="route('supply.procurement.budgets.edit', $budget)" mode="outline">Edit budget</x-supply.button>
    </div>
</section>

<section>
    <dl class="structured-data">
        <dt>Company</dt>
        <dd>{{ $budget->company?->name }}</dd>
        <dt>Period</dt>
        <dd>{{ $budget->date_from?->toDateString() }} - {{ $budget->date_to?->toDateString() }}</dd>
        <dt>Period type</dt>
        <dd>{{ ucfirst($budget->period_type?->value ?? $budget->period_type) }}</dd>
        <dt>Total amount</dt>
        <dd>{{ number_format((float) $budget->total_amount, 2) }} {{ $budget->currency }}</dd>
        <dt>Owner</dt>
        <dd>{{ $budget->owner?->name ?? 'No owner' }}</dd>
        <dt>Notes</dt>
        <dd>{{ $budget->notes ?? 'No notes' }}</dd>
    </dl>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Budget lines</p>
            <h2>Allocated scopes</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Scope</th>
                <th>Project</th>
                <th>Manager</th>
                <th>Allocated</th>
                <th>Committed</th>
                <th>Spent</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($budget->lines as $line)
                <tr>
                    <td>
                        <strong>{{ $line->product?->sku ?? $line->category ?? $line->supplier?->name ?? 'General' }}</strong>
                        <span>{{ $line->product?->name ?? $line->supplier?->name ?? 'Company budget line' }}</span>
                    </td>
                    <td>{{ $line->project_name ?? 'No project' }}</td>
                    <td>{{ $line->manager_name ?? 'No manager' }}</td>
                    <td>{{ number_format((float) $line->amount, 2) }}</td>
                    <td>{{ number_format((float) ($line->committed_amount ?? 0), 2) }}</td>
                    <td>{{ number_format((float) ($line->spent_amount ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No budget lines yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">New line</p>
            <h2>Add budget allocation</h2>
        </div>
    </div>

    <form method="POST" action="{{ route('supply.procurement.budgets.lines.store', $budget) }}" class="form-grid">
        @csrf

        <label>
            <span>Supplier</span>
            <select class="select select-bordered" name="supplier_id">
                <option value="">Any supplier</option>
                @forelse ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                @empty
                    <option value="">No suppliers</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Product</span>
            <select class="select select-bordered" name="product_id">
                <option value="">Any product</option>
                @forelse ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->sku }} - {{ $product->name }}</option>
                @empty
                    <option value="">No products</option>
                @endforelse
            </select>
        </label>

        <label>
            <span>Category</span>
            <input class="input input-bordered" name="category" value="{{ old('category') }}">
        </label>

        <label>
            <span>Project</span>
            <input class="input input-bordered" name="project_name" value="{{ old('project_name') }}">
        </label>

        <label>
            <span>Manager</span>
            <input class="input input-bordered" name="manager_name" value="{{ old('manager_name') }}">
        </label>

        <label>
            <span>Amount</span>
            <input class="input input-bordered" type="number" step="0.0001" min="0" name="amount" value="{{ old('amount') }}" required>
        </label>

        <label>
            <span>Committed amount</span>
            <input class="input input-bordered" type="number" step="0.0001" min="0" name="committed_amount" value="{{ old('committed_amount') }}">
        </label>

        <label>
            <span>Spent amount</span>
            <input class="input input-bordered" type="number" step="0.0001" min="0" name="spent_amount" value="{{ old('spent_amount') }}">
        </label>

        <div class="form-actions">
            <x-supply.button type="submit">Add budget line</x-supply.button>
        </div>
    </form>
</section>
@endsection
