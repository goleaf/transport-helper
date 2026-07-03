@extends('layouts.app')

@section('title')
Procurement Reports
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Procurement controls</p>
        <h1>Procurement Reports</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Gate check</p>
            <h2>Run procurement gate</h2>
        </div>
    </div>

    <form method="POST" action="{{ route('supply.procurement.gate') }}" class="form-grid">
        @csrf

        <label>
            <span>Subject type</span>
            <select class="select select-bordered" name="type" required>
                <option value="proposal">Order proposal</option>
                <option value="supplier_order">Supplier order</option>
                <option value="scenario">Calculation scenario</option>
            </select>
        </label>

        <label>
            <span>Subject ID</span>
            <input class="input input-bordered" type="number" min="1" name="id" required>
        </label>

        <label>
            <span>Action</span>
            <select class="select select-bordered" name="action" required>
                <option value="approve_order_proposal">Approve order proposal</option>
                <option value="convert_to_supplier_order">Convert to supplier order</option>
                <option value="approve_supplier_email">Approve supplier email</option>
                <option value="send_supplier_email">Send supplier email</option>
                <option value="create_proposal_from_scenario">Create proposal from scenario</option>
            </select>
        </label>

        <div class="form-actions">
            <x-supply.button type="submit">Run gate</x-supply.button>
        </div>
    </form>
</section>

<section class="grid" aria-label="Procurement report summary">
    <div class="stat metric"><span class="stat-title">Budgets</span><strong class="stat-value">{{ $budgetReport['summary']['budgets_count'] ?? 0 }}</strong></div>
    <div class="stat metric"><span class="stat-title">Budget total</span><strong class="stat-value">{{ number_format((float) ($budgetReport['summary']['total_amount'] ?? 0), 2) }}</strong></div>
    <div class="stat metric"><span class="stat-title">Approval requests</span><strong class="stat-value">{{ $approvalsReport['summary']['requests_count'] ?? 0 }}</strong></div>
    <div class="stat metric"><span class="stat-title">Pending approvals</span><strong class="stat-value">{{ $approvalsReport['summary']['pending_count'] ?? 0 }}</strong></div>
    <div class="stat metric"><span class="stat-title">Exceptions</span><strong class="stat-value">{{ $exceptionsReport['summary']['exceptions_count'] ?? 0 }}</strong></div>
    <div class="stat metric"><span class="stat-title">Supplier spend</span><strong class="stat-value">{{ number_format((float) ($supplierSpendReport['summary']['estimated_spend'] ?? 0), 2) }}</strong></div>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Export</p>
            <h2>Download procurement report</h2>
        </div>
    </div>

    <form method="POST" action="{{ route('supply.procurement.reports.export') }}" class="form-grid">
        @csrf

        <label>
            <span>Report type</span>
            <select class="select select-bordered" name="report_type" required>
                <option value="budget_status">Budget status</option>
                <option value="approvals">Approvals</option>
                <option value="exceptions">Exceptions</option>
                <option value="supplier_spend">Supplier spend</option>
            </select>
        </label>

        <label>
            <span>Company ID</span>
            <input class="input input-bordered" type="number" min="1" name="company_id" value="{{ $filters['company_id'] ?? '' }}">
        </label>

        <div class="form-actions">
            <x-supply.button type="submit" mode="outline">Export CSV</x-supply.button>
        </div>
    </form>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Budgets</p>
            <h2>Budget status</h2>
        </div>
    </div>

    @include('supply.procurement.partials.report-table', [
        'rows' => $budgetReport['rows'] ?? [],
        'columns' => [
            ['key' => 'name', 'label' => 'Budget'],
            ['key' => 'period', 'label' => 'Period'],
            ['key' => 'currency', 'label' => 'Currency'],
            ['key' => 'total_amount', 'label' => 'Total'],
            ['key' => 'line_amount', 'label' => 'Allocated lines'],
            ['key' => 'status', 'label' => 'Status'],
        ],
    ])
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Approvals</p>
            <h2>Approval requests</h2>
        </div>
    </div>

    @include('supply.procurement.partials.report-table', [
        'rows' => $approvalsReport['rows'] ?? [],
        'columns' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'amount', 'label' => 'Amount'],
            ['key' => 'currency', 'label' => 'Currency'],
            ['key' => 'required_permission', 'label' => 'Permission'],
            ['key' => 'requested_by', 'label' => 'Requested by'],
            ['key' => 'reason', 'label' => 'Reason'],
        ],
    ])
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Exceptions</p>
            <h2>Exception requests</h2>
        </div>
    </div>

    @include('supply.procurement.partials.report-table', [
        'rows' => $exceptionsReport['rows'] ?? [],
        'columns' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'requested_by', 'label' => 'Requested by'],
            ['key' => 'approved_by', 'label' => 'Approved by'],
            ['key' => 'reason', 'label' => 'Reason'],
        ],
    ])
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Supplier spend</p>
            <h2>Estimated supplier spend</h2>
        </div>
    </div>

    @include('supply.procurement.partials.report-table', [
        'rows' => $supplierSpendReport['rows'] ?? [],
        'columns' => [
            ['key' => 'supplier', 'label' => 'Supplier'],
            ['key' => 'orders_count', 'label' => 'Orders'],
            ['key' => 'estimated_spend', 'label' => 'Estimated spend'],
        ],
    ])
</section>
@endsection
