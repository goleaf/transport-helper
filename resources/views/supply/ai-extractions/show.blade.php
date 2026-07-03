<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Email Extraction {{ $extraction->id }}</title>
</head>
<body>
    <main>
        <header>
            <p><a href="{{ route('supply.emails.show', $extraction->emailMessage) }}">Back to email</a></p>
            <h1>AI Email Extraction {{ $extraction->id }}</h1>
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <section>
                <h2>Errors</h2>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            <dl>
                <dt>Email subject</dt>
                <dd>{{ $extraction->emailMessage?->subject }}</dd>

                <dt>Supplier order</dt>
                <dd>{{ $extraction->emailMessage?->relatedSupplierOrder?->order_number }}</dd>

                <dt>Confidence</dt>
                <dd>{{ $validation['confidence'] }}</dd>

                <dt>Validation status</dt>
                <dd>{{ $validation['status'] }}</dd>

                <dt>Requires human review</dt>
                <dd>{{ $extraction->requires_human_review ? 'Yes' : 'No' }}</dd>

                <dt>Review reason</dt>
                <dd>{{ $extraction->review_reason }}</dd>
            </dl>
        </section>

        <section>
            <h2>Validation reasons</h2>
            <ul>
                @forelse ($validation['reasons'] as $reason)
                    <li>{{ $reason }}</li>
                @empty
                    <li>No validation issues.</li>
                @endforelse
            </ul>
        </section>

        <section>
            <h2>AI output</h2>
            <pre>{{ json_encode($extraction->output_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>

        <section>
            <h2>Review actions</h2>
            @if ($canAccept)
                <form method="post" action="{{ route('supply.ai-extractions.accept', $extraction) }}">
                    @csrf
                    <button type="submit">Accept</button>
                </form>
            @endif

            @if ($canReject)
                <form method="post" action="{{ route('supply.ai-extractions.reject', $extraction) }}">
                    @csrf
                    <button type="submit">Reject</button>
                </form>
            @endif

            @if ($canRequestHumanReview)
                <form method="post" action="{{ route('supply.ai-extractions.request-human-review', $extraction) }}">
                    @csrf
                    <button type="submit">Request human review</button>
                </form>
            @endif
        </section>
    </main>
</body>
</html>
