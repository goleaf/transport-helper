@extends('layouts.app')

@section('title')
Procurement Exception
@endsection

@section('content')
<x-supply.page-header title="Procurement Exception" subtitle="Exceptions satisfy gates only after approval." :status="$exception->status" :back-url="route('supply.procurement.exceptions.index')" />

@if (session('status'))
    <x-supply.alert tone="success">{{ session('status') }}</x-supply.alert>
@endif

@include('supply.procurement.partials.tabs')

<section>
    <dl class="structured-data">
        <dt>Company</dt>
        <dd>{{ $exception->company?->name }}</dd>
        <dt>Type</dt>
        <dd><x-supply.human-label :value="$exception->exception_type" /></dd>
        <dt>Subject</dt>
        <dd>{{ class_basename($exception->exceptable_type) }} #{{ $exception->exceptable_id }}</dd>
        <dt>Reason</dt>
        <dd>{{ $exception->reason }}</dd>
        <dt>Requested by</dt>
        <dd>{{ $exception->requestedBy?->name ?? 'System' }}</dd>
        <dt>Approved by</dt>
        <dd>{{ $exception->approvedBy?->name ?? 'Not approved' }}</dd>
        <dt>Rejected by</dt>
        <dd>{{ $exception->rejectedBy?->name ?? 'Not rejected' }}</dd>
        <dt>Rejection reason</dt>
        <dd>{{ $exception->rejection_reason ?? 'No rejection reason' }}</dd>
    </dl>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Decision</p>
            <h2>Approve or reject exception</h2>
        </div>
    </div>

    <div class="grid">
        <form method="POST" action="{{ route('supply.procurement.exceptions.approve', $exception) }}" class="form-grid">
            @csrf
            <label>
                <span>Approval note</span>
                <textarea class="textarea textarea-bordered" name="note">{{ old('note') }}</textarea>
            </label>
            <div class="form-actions">
                <x-supply.button type="submit" variant="success">Approve exception</x-supply.button>
            </div>
        </form>

        <form method="POST" action="{{ route('supply.procurement.exceptions.reject', $exception) }}" class="form-grid">
            @csrf
            <label>
                <span>Rejection reason</span>
                <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
            </label>
            <div class="form-actions">
                <x-supply.button type="submit" variant="warning">Reject exception</x-supply.button>
            </div>
        </form>
    </div>
</section>
@endsection
