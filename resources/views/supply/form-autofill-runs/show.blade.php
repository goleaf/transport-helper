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
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <section>
            <h2>Status</h2>
            <dl>
                <dt>Status</dt>
                <dd>{{ $run->status instanceof \BackedEnum ? $run->status->value : $run->status }}</dd>
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

        <section>
            <h2>Original email</h2>
            <dl>
                <dt>Subject</dt>
                <dd>{{ $run->emailMessage?->subject }}</dd>
                <dt>From</dt>
                <dd>{{ $run->emailMessage?->from_email }}</dd>
            </dl>
            <pre>{{ $run->emailMessage?->body_text }}</pre>
            <h3>Attachments</h3>
            <ul>
                @forelse ($run->emailMessage?->attachments ?? [] as $attachment)
                    <li>{{ $attachment->original_filename }}</li>
                @empty
                    <li>No attachments.</li>
                @endforelse
            </ul>
        </section>

        <section>
            <h2>Form fields</h2>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Extracted value</th>
                        <th>Normalized value</th>
                        <th>Final value</th>
                        <th>Confidence</th>
                        <th>Source excerpt</th>
                        <th>Warning</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($run->fieldValues as $field)
                        <tr>
                            <td>{{ $field->field_key }}</td>
                            <td>{{ $field->extracted_value }}</td>
                            <td>{{ $field->normalized_value }}</td>
                            <td>{{ $field->final_value }}</td>
                            <td>{{ $field->confidence }}</td>
                            <td>{{ $field->source_excerpt }}</td>
                            <td>{{ $field->review_reason }}</td>
                            <td>
                                <form method="post" action="{{ route('supply.form-autofill-runs.fields.accept', [$run, $field]) }}">
                                    @csrf
                                    <button type="submit">Accept</button>
                                </form>
                                <form method="post" action="{{ route('supply.form-autofill-runs.fields.update', [$run, $field]) }}">
                                    @csrf
                                    <input name="final_value" value="{{ $field->final_value }}">
                                    <button type="submit">Edit</button>
                                </form>
                                <form method="post" action="{{ route('supply.form-autofill-runs.fields.reject', [$run, $field]) }}">
                                    @csrf
                                    <button type="submit">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No fields.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Run actions</h2>
            <form method="post" action="{{ route('supply.form-autofill-runs.validate', $run) }}">
                @csrf
                <button type="submit">Validate run</button>
            </form>

            @if ($canApply)
                <form method="post" action="{{ route('supply.form-autofill-runs.apply', $run) }}">
                    @csrf
                    <button type="submit">Apply</button>
                </form>
            @endif

            <form method="post" action="{{ route('supply.form-autofill-runs.export', $run) }}">
                @csrf
                <select name="format">
                    <option value="json">JSON</option>
                    <option value="csv">CSV</option>
                    <option value="internal_html">Internal HTML</option>
                </select>
                <button type="submit">Export</button>
            </form>
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
    </main>
</body>
</html>
