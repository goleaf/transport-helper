@extends('layouts.app')

@section('title')
AI Email Extraction {{ $extraction->id }}
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.emails.show', $extraction->emailMessage) }}">Back to email</a></p>
    <h1>AI Email Extraction {{ $extraction->id }}</h1>
</header>

<p>AI extraction is not applied directly. User acceptance only approves extracted data for later application.</p>

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

        <dt>Review status</dt>
        <dd>@include('supply.ai-extractions.partials.status-badge', ['extraction' => $extraction])</dd>

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
    <h2>Extraction summary</h2>
    @include('supply.ai-extractions.partials.output-summary', ['output' => $extraction->output_json ?? []])
</section>

<section>
    <h2>Extracted details</h2>
    <x-supply.structured-value :value="$extraction->output_json ?? []" />
</section>

        <section>
            <h2>Review actions</h2>
            @include('supply.ai-extractions.partials.review-actions', [
                'extraction' => $extraction,
                'canAccept' => $canAccept,
                'canReject' => $canReject,
                'canRequestHumanReview' => $canRequestHumanReview,
            ])
        </section>

        <section>
            <h2>Apply as supplier confirmation</h2>
            @php
                $output = is_array($extraction->output_json ?? null) ? $extraction->output_json : [];
                $emailType = $output['email_type'] ?? null;
                $hasConfirmationData = in_array($emailType, ['supplier_confirmation', 'date_update', 'quantity_mismatch'], true)
                    || ! empty($output['confirmed_items']);
            @endphp
            @if ($extraction->accepted_at && ! $extraction->rejected_at && $hasConfirmationData && $canApplySupplierConfirmation)
                <form method="POST" action="{{ route('supply.ai-extractions.apply-supplier-confirmation', $extraction) }}">
                    @csrf
                    <label>Supplier order ID <input type="number" name="supplier_order_id" value="{{ $extraction->emailMessage?->related_supplier_order_id }}"></label>
                    <label><input type="checkbox" name="update_inbound" value="1" checked> Update inbound</label>
                    <label><input type="checkbox" name="update_logistics" value="1" checked> Update logistics</label>
                    <label><input type="checkbox" name="allow_missing_items" value="1"> Allow missing items</label>
                    <label><input type="checkbox" name="allow_over_confirmation" value="1"> Allow over confirmation</label>
                    <label><input type="checkbox" name="confirm_apply" value="1" required> Confirm apply</label>
                    <button type="submit">Apply supplier confirmation</button>
                </form>
            @elseif (! $extraction->accepted_at)
                <p>Accept extraction before applying it.</p>
            @else
                <p>This extraction is not ready to apply as supplier confirmation.</p>
            @endif
        </section>
@endsection
