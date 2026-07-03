<section>
    <h2>Status</h2>
    <dl>
        <dt>Template</dt>
        <dd>{{ $run->formTemplate?->name }}</dd>
        <dt>Context</dt>
        <dd>{{ $contextLabel }}</dd>
        <dt>Status</dt>
        <dd>@include('supply.form-autofill-runs.partials.status-badge', ['status' => $run->status])</dd>
        <dt>Total confidence</dt>
        <dd>{{ $run->confidence }}</dd>
        <dt>Fields requiring review</dt>
        <dd>{{ $fieldsRequiringReviewCount }}</dd>
        <dt>Validation errors</dt>
        <dd><x-supply.structured-value :value="$run->validation_errors_json ?? []" /></dd>
        <dt>Warnings</dt>
        <dd><x-supply.structured-value :value="$run->warnings_json ?? []" /></dd>
    </dl>
</section>
