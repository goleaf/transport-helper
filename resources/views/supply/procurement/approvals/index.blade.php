@extends('layouts.app')

@section('title')
Procurement Approvals
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Procurement controls</p>
        <h1>Approval Requests</h1>
    </div>
</header>

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Manual sign-off</p>
            <h2>Request approval</h2>
        </div>
    </div>

    <form method="POST" action="{{ route('supply.procurement.approvals.request') }}" class="form-grid">
        @csrf

        <label>
            <span>Subject type</span>
            <select class="select select-bordered" name="approvable_type" required>
                <option value="proposal" @selected(old('approvable_type') === 'proposal')>Order proposal</option>
                <option value="supplier_order" @selected(old('approvable_type') === 'supplier_order')>Supplier order</option>
                <option value="scenario" @selected(old('approvable_type') === 'scenario')>Calculation scenario</option>
            </select>
        </label>

        <label>
            <span>Subject ID</span>
            <input class="input input-bordered" type="number" min="1" name="approvable_id" value="{{ old('approvable_id') }}" required>
        </label>

        <label>
            <span>Reason</span>
            <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
        </label>

        <div class="form-actions">
            <x-supply.button type="submit">Request approval</x-supply.button>
        </div>
    </form>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Requests</p>
            <h2>Manager decisions</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Status</th>
                <th>Subject</th>
                <th>Amount</th>
                <th>Required authority</th>
                <th>Requested by</th>
                <th>Reason</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($approvalRequests as $approvalRequest)
                <tr>
                    <td><x-supply.status-badge :status="$approvalRequest->status" /></td>
                    <td>
                        <strong>{{ class_basename($approvalRequest->approvable_type) }}</strong>
                        <span>#{{ $approvalRequest->approvable_id }}</span>
                    </td>
                    <td>{{ $approvalRequest->amount !== null ? number_format((float) $approvalRequest->amount, 2).' '.$approvalRequest->currency : 'Not estimated' }}</td>
                    <td>{{ $approvalRequest->required_permission ?? $approvalRequest->required_role ?? 'Manager fallback' }}</td>
                    <td>{{ $approvalRequest->requestedBy?->name ?? 'System' }}</td>
                    <td>{{ $approvalRequest->reason }}</td>
                    <td><x-supply.table-action :href="route('supply.procurement.approvals.show', $approvalRequest)" label="Open" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No procurement approval requests.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $approvalRequests->links() }}
</section>
@endsection
