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
    <section>
        <h2>Application Readiness</h2>
        <dl>
            <dt>Can apply later</dt>
            <dd>{{ session('application_gate.can_apply') ? 'Yes' : 'No' }}</dd>
            <dt>Target action</dt>
            <dd>{{ session('application_gate.target_action') }}</dd>
            <dt>Blocking reasons</dt>
            <dd>{{ implode(', ', session('application_gate.blocking_reasons', [])) }}</dd>
        </dl>
    </section>
@endif

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

@include('supply.form-autofill-runs.partials.email-panel', ['run' => $run])

<section>
    <h2>Status</h2>
    <dl>
        <dt>Template</dt>
        <dd>{{ $run->formTemplate?->name }}</dd>
        <dt>Context</dt>
        <dd>{{ $run->formTemplate?->context_type instanceof \BackedEnum ? $run->formTemplate->context_type->value : $run->formTemplate?->context_type }}</dd>
        <dt>Status</dt>
        <dd>@include('supply.form-autofill-runs.partials.status-badge', ['status' => $run->status])</dd>
        <dt>Total confidence</dt>
        <dd>{{ $run->confidence }}</dd>
        <dt>Fields requiring review</dt>
        <dd>{{ $run->fieldValues->where('requires_review', true)->count() }}</dd>
        <dt>Validation errors</dt>
        <dd><x-supply.structured-value :value="$run->validation_errors_json ?? []" /></dd>
        <dt>Warnings</dt>
        <dd><x-supply.structured-value :value="$run->warnings_json ?? []" /></dd>
    </dl>
</section>

        @include('supply.form-autofill-runs.partials.fields-table', ['run' => $run])
        @include('supply.form-autofill-runs.partials.export-panel', ['run' => $run])
        @include('supply.form-autofill-runs.partials.application-gate-panel', ['run' => $run])

        <section>
            <h2>Apply as supplier confirmation</h2>
            @php
                $contextType = $run->formTemplate?->context_type instanceof \BackedEnum ? $run->formTemplate->context_type->value : (string) $run->formTemplate?->context_type;
                $runStatus = $run->status instanceof \BackedEnum ? $run->status->value : (string) $run->status;
                $compatibleContext = in_array($contextType, ['supplier_confirmation', 'ready_date_update', 'quantity_mismatch'], true);
            @endphp
            @if ($runStatus === 'validated' && $compatibleContext && $canApplySupplierConfirmation)
                <form method="POST" action="{{ route('supply.form-autofill-runs.apply-supplier-confirmation', $run) }}">
                    @csrf
                    <label>Supplier order ID <input type="number" name="supplier_order_id" value="{{ $run->emailMessage?->related_supplier_order_id }}"></label>
                    <label><input type="checkbox" name="update_inbound" value="1" checked> Update inbound</label>
                    <label><input type="checkbox" name="update_logistics" value="1" checked> Update logistics</label>
                    <label><input type="checkbox" name="allow_missing_items" value="1"> Allow missing items</label>
                    <label><input type="checkbox" name="allow_over_confirmation" value="1"> Allow over confirmation</label>
                    <label><input type="checkbox" name="confirm_apply" value="1" required> Confirm apply</label>
                    <button type="submit">Apply supplier confirmation</button>
                </form>
            @elseif ($runStatus !== 'validated')
                <p>Validate autofill run before applying it.</p>
            @else
                <p>This form autofill run is not compatible with supplier confirmation application.</p>
            @endif
        </section>

        <section>
            <h2>Apply as carrier quote</h2>
            @php
                $contextType = $run->formTemplate?->context_type instanceof \BackedEnum ? $run->formTemplate->context_type->value : (string) $run->formTemplate?->context_type;
                $runStatus = $run->status instanceof \BackedEnum ? $run->status->value : (string) $run->status;
            @endphp
            @if ($runStatus === 'validated' && $contextType === 'carrier_quote' && $canApplyCarrierQuote)
                <form method="POST" action="{{ route('supply.form-autofill-runs.apply-carrier-quote', $run) }}">
                    @csrf
                    <label>Supplier order ID <input type="number" name="supplier_order_id" value="{{ $run->emailMessage?->related_supplier_order_id }}"></label>
                    <label><input type="checkbox" name="allow_missing_delivery_date" value="1"> Allow missing delivery date</label>
                    <label><input type="checkbox" name="allow_zero_price" value="1"> Allow zero price</label>
                    <label><input type="checkbox" name="confirm_apply" value="1" required> Confirm apply</label>
                    <button type="submit">Create carrier quote candidate</button>
                </form>
            @elseif ($runStatus !== 'validated')
                <p>Validate autofill run before applying it.</p>
            @else
                <p>This form autofill run is not compatible with carrier quote application.</p>
            @endif
        </section>

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
