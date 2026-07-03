<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supply Email {{ $email->id }}</title>
</head>
<body>
    <main>
        <x-supply.navigation />

        <header>
            <p><a href="{{ route('supply.emails.index') }}">Back to emails</a></p>
            <h1>{{ $email->subject }}</h1>
            @if (($email->direction instanceof \BackedEnum ? $email->direction->value : $email->direction) === 'inbound')
                <p><a href="{{ route('supply.emails.autofill.create', $email) }}">Autofill form from this email</a></p>
            @endif
        </header>

        <section>
            <dl>
                <dt>Message ID</dt>
                <dd>{{ $email->message_id }}</dd>

                <dt>Thread ID</dt>
                <dd>{{ $email->thread_id }}</dd>

                <dt>From</dt>
                <dd>{{ $email->from_email }}</dd>

                <dt>Supplier</dt>
                <dd>{{ $email->relatedSupplier?->name }}</dd>

                <dt>Supplier order</dt>
                <dd>{{ $email->relatedSupplierOrder?->order_number }}</dd>

                <dt>Status</dt>
                <dd>{{ $email->status }}</dd>
            </dl>
        </section>

        <section>
            <h2>Body</h2>
            <pre>{{ $email->body_text }}</pre>
        </section>

        <section>
            <h2>Attachments</h2>
            <ul>
                @forelse ($email->attachments as $attachment)
                    <li>{{ $attachment->original_filename }} {{ $attachment->mime_type }} {{ $attachment->size_bytes }}</li>
                @empty
                    <li>No attachments.</li>
                @endforelse
            </ul>
        </section>

        <section>
            <h2>AI Extractions</h2>
            @if ($canAnalyze)
                <form method="post" action="{{ route('supply.emails.analyze', $email) }}">
                    @csrf
                    <input type="hidden" name="sync" value="1">
                    <label>
                        Analyzer
                        <select name="analyzer">
                            <option value="rule_based">Rule based</option>
                            <option value="fake">Fake</option>
                            <option value="external">External placeholder</option>
                        </select>
                    </label>
                    <button type="submit">Analyze email</button>
                </form>
            @endif

            <table>
                <thead>
                    <tr>
                            <th>Prompt</th>
                            <th>Email type</th>
                            <th>Confidence</th>
                        <th>Human review</th>
                        <th>Review reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($email->aiEmailExtractions as $extraction)
                        <tr>
                            <td>{{ $extraction->prompt_version instanceof \BackedEnum ? $extraction->prompt_version->value : $extraction->prompt_version }}</td>
                            <td>{{ $extraction->output_json['email_type'] ?? 'unclear' }}</td>
                            <td>{{ $extraction->confidence }}</td>
                            <td>{{ $extraction->requires_human_review ? 'Yes' : 'No' }}</td>
                            <td>{{ $extraction->review_reason }}</td>
                            <td>{{ $extraction->accepted_at ? 'accepted' : ($extraction->rejected_at ? 'rejected' : 'pending') }}</td>
                            <td><a href="{{ route('supply.ai-extractions.show', $extraction) }}">Review</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">No AI extractions.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Form Autofill Runs</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Template</th>
                        <th>Status</th>
                        <th>Confidence</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($email->formAutofillRuns as $run)
                        <tr>
                            <td>{{ $run->id }}</td>
                            <td>{{ $run->formTemplate?->name }}</td>
                            <td>{{ $run->status instanceof \BackedEnum ? $run->status->value : $run->status }}</td>
                            <td>{{ $run->confidence }}</td>
                            <td>{{ $run->created_at?->toDateTimeString() }}</td>
                            <td><a href="{{ route('supply.form-autofill-runs.show', $run) }}">Review run</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No form autofill runs.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
