@extends('layouts.app')

@section('title')
Form Autofill Run {{ $run->id }}
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.emails.show', $run->emailMessage) }}">Back to email</a></p>
    <h1>Form Autofill Run {{ $run->id }}</h1>
    <p>This stage does not apply business changes. Supplier confirmation, carrier quote and logistics application are handled in the next workflow stage.</p>
</header>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

@if (session('application_gate'))
    <x-supply.application-gate-summary :gate="session('application_gate')" />
@endif

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

@include('supply.form-autofill-runs.partials.email-panel', ['run' => $run])

<x-supply.form-autofill-status-summary :run="$run" />

        @include('supply.form-autofill-runs.partials.fields-table', ['run' => $run])
        @include('supply.form-autofill-runs.partials.export-panel', ['run' => $run])
        @include('supply.form-autofill-runs.partials.application-gate-panel', ['run' => $run])

        <x-supply.form-autofill-applications
            :run="$run"
            :can-apply-supplier-confirmation="$canApplySupplierConfirmation"
            :can-apply-carrier-quote="$canApplyCarrierQuote"
        />

        <section>
    <h2>Audit history</h2>
    <ul>
        @forelse ($auditLogs as $auditLog)
            <li>{{ $auditLog->created_at?->toDateTimeString() }} {{ $auditLog->event_type }} {{ $auditLog->user?->name }}</li>
        @empty
            <li>No audit logs.</li>
        @endforelse
    </ul>
</section>
@endsection
