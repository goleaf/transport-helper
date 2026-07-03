@extends('layouts.app')

@section('title')
Procurement Approval
@endsection

@section('content')
<x-supply.page-header title="Procurement Approval Request" subtitle="Manager sign-off is explicit and audited." :status="$approvalRequest->status" :back-url="route('supply.procurement.approvals.index')" />

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <dl class="structured-data">
        <dt>Company</dt>
        <dd>{{ $approvalRequest->company?->name }}</dd>
        <dt>Subject</dt>
        <dd>{{ class_basename($approvalRequest->approvable_type) }} #{{ $approvalRequest->approvable_id }}</dd>
        <dt>Amount</dt>
        <dd>{{ $approvalRequest->amount !== null ? number_format((float) $approvalRequest->amount, 2).' '.$approvalRequest->currency : 'Not estimated' }}</dd>
        <dt>Required role</dt>
        <dd>{{ $approvalRequest->required_role ?? 'No role-specific requirement' }}</dd>
        <dt>Required permission</dt>
        <dd>{{ $approvalRequest->required_permission ?? 'No permission-specific requirement' }}</dd>
        <dt>Requested by</dt>
        <dd>{{ $approvalRequest->requestedBy?->name ?? 'System' }}</dd>
        <dt>Reason</dt>
        <dd>{{ $approvalRequest->reason }}</dd>
        <dt>Expires at</dt>
        <dd>{{ $approvalRequest->expires_at?->toDateTimeString() ?? 'No expiry' }}</dd>
    </dl>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Decision</p>
            <h2>Approve or reject</h2>
        </div>
    </div>

    <div class="grid">
        <form method="POST" action="{{ route('supply.procurement.approvals.approve', $approvalRequest) }}" class="form-grid">
            @csrf
            <label>
                <span>Approval note</span>
                <textarea class="textarea textarea-bordered" name="note">{{ old('note') }}</textarea>
            </label>
            <div class="form-actions">
                <x-supply.button type="submit" variant="success">Approve request</x-supply.button>
            </div>
        </form>

        <form method="POST" action="{{ route('supply.procurement.approvals.reject', $approvalRequest) }}" class="form-grid">
            @csrf
            <label>
                <span>Rejection reason</span>
                <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
            </label>
            <div class="form-actions">
                <x-supply.button type="submit" variant="warning">Reject request</x-supply.button>
            </div>
        </form>
    </div>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Decision history</p>
            <h2>Audit-visible decisions</h2>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Decision</th>
                <th>By</th>
                <th>Note</th>
                <th>Decided at</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($approvalRequest->decisions as $decision)
                <tr>
                    <td><x-supply.status-badge :status="$decision->decision" /></td>
                    <td>{{ $decision->decisionBy?->name ?? 'System' }}</td>
                    <td>{{ $decision->note ?? 'No note' }}</td>
                    <td>{{ $decision->decided_at?->toDateTimeString() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No decisions yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
