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

        <x-supply.ai-extraction-applications
            :extraction="$extraction"
            :can-apply-supplier-confirmation="$canApplySupplierConfirmation"
            :can-apply-carrier-quote="$canApplyCarrierQuote"
        />
@endsection
