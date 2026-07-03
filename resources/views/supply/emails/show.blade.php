@extends('layouts.app')

@section('title')
Supply Email {{ $email->id }}
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.emails.index') }}">Back to emails</a></p>
    <h1>{{ $email->subject }}</h1>
    @if ($email->is_inbound)
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
        <dd><x-supply.status-badge :status="$email->status" /></dd>
    </dl>
</section>

<section>
    <h2>Body</h2>
    <div class="message-body">{{ $email->body_text }}</div>
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
                <select class="select select-bordered select-primary" name="analyzer">
                    <option value="rule_based">Rule based</option>
                    <option value="fake">Fake</option>
                    <option value="external">External placeholder</option>
                </select>
            </label>
            <x-supply.button type="submit">Analyze email</x-supply.button>
        </form>
    @endif

    <table class="table table-zebra">
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
                    <td><x-supply.human-label :value="$extraction->prompt_version" /></td>
                    <td>{{ $extraction->output_json['email_type'] ?? 'unclear' }}</td>
                    <td>{{ $extraction->confidence }}</td>
                    <td>{{ $extraction->requires_human_review ? 'Yes' : 'No' }}</td>
                    <td>{{ $extraction->review_reason }}</td>
                    <td>{{ $extraction->accepted_at ? 'accepted' : ($extraction->rejected_at ? 'rejected' : 'pending') }}</td>
                    <td><x-supply.table-action :href="route('supply.ai-extractions.show', $extraction)" label="Review" /></td>
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
    <table class="table table-zebra">
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
                    <td><x-supply.status-badge :status="$run->status" /></td>
                    <td>{{ $run->confidence }}</td>
                    <td>{{ $run->created_at?->toDateTimeString() }}</td>
                    <td><x-supply.table-action :href="route('supply.form-autofill-runs.show', $run)" label="Review run" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No form autofill runs.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
