<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Autofill Run {{ $run->id }}</title>
</head>
<body>
    <main>
        <x-supply.navigation />

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
                <dd>{{ json_encode($run->validation_errors_json ?? []) }}</dd>
                <dt>Warnings</dt>
                <dd>{{ json_encode($run->warnings_json ?? []) }}</dd>
            </dl>
        </section>

        @include('supply.form-autofill-runs.partials.fields-table', ['run' => $run])
        @include('supply.form-autofill-runs.partials.export-panel', ['run' => $run])
        @include('supply.form-autofill-runs.partials.application-gate-panel', ['run' => $run])

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
    </main>
</body>
</html>
