@extends('layouts.app')

@section('title')
Order Proposal Item: {{ $item->product?->sku }}
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.proposals.show', $proposal) }}">Back to proposal</a></p>
    <h1>Order Proposal Item: {{ $item->product?->sku }}</h1>
    <p>{{ $item->product?->name }} · {{ $proposal->supplier?->name }} · Proposal #{{ $proposal->id }}</p>
    <p>
        @include('supply.proposals.partials.status-badge', ['status' => $item->status])
        @if ($item->requires_human_review)
            <strong>Human review required</strong>
        @endif
    </p>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

@if ($errors->any())
    <section class="alert alert-warning warning">
        <h2>Errors</h2>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </section>
@endif

@include('supply.proposals.partials.timeline', ['item' => $item])
@include('supply.proposals.partials.formula-summary', ['item' => $item])

<section>
    <h2>Explanation</h2>
    @include('supply.proposals.partials.explanation', ['explanation' => $item->explanation_json ?? []])
</section>

<section>
    <h2>Warnings</h2>
    @if ($item->requires_human_review)
        <p class="alert alert-warning warning">Human review required.</p>
    @endif
    <ul>
        @forelse (($item->warnings_json ?? []) as $warning)
            <li><x-supply.structured-value :value="$warning" /></li>
        @empty
            <li>No warnings.</li>
        @endforelse
    </ul>
</section>

<section>
    <h2>Actions</h2>
    @include('supply.proposals.partials.item-actions', [
        'proposal' => $proposal,
        'item' => $item,
        'canApproveItem' => $canApproveItem,
        'canAdjustItem' => $canAdjustItem,
        'canRejectItem' => $canRejectItem,
        'isConverted' => $isConverted,
    ])
</section>

<section>
    <h2>Audit history</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Event</th>
                <th>User</th>
                <th>Metadata</th>
                <th>Created at</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($auditLogs as $auditLog)
                <tr>
                    <td>{{ $auditLog->event_type }}</td>
                    <td>{{ $auditLog->user?->name }}</td>
                    <td><x-supply.structured-value :value="$auditLog->metadata_json ?? []" /></td>
                    <td>{{ $auditLog->created_at?->toDateTimeString() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No audit events yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
