<section>
    <h2>Source</h2>
    <dl>
        <dt>Source type</dt>
        <dd>{{ $confirmation->source_type ?? 'manual' }}</dd>
        <dt>Source ID</dt>
        <dd>{{ $confirmation->source_id ?? '' }}</dd>
        <dt>Email</dt>
        <dd>
            @if ($confirmation->emailMessage)
                <a href="{{ route('supply.emails.show', $confirmation->emailMessage) }}">{{ $confirmation->emailMessage->subject }}</a>
            @else
                Not linked
            @endif
        </dd>
        <dt>AI extraction</dt>
        <dd>
            @if ($confirmation->aiEmailExtraction)
                <a href="{{ route('supply.ai-extractions.show', $confirmation->aiEmailExtraction) }}">Extraction #{{ $confirmation->aiEmailExtraction->id }}</a>
            @else
                Not linked
            @endif
        </dd>
        <dt>Form autofill run</dt>
        <dd>
            @if ($confirmation->formAutofillRun)
                <a href="{{ route('supply.form-autofill-runs.show', $confirmation->formAutofillRun) }}">Run #{{ $confirmation->formAutofillRun->id }}</a>
            @else
                Not linked
            @endif
        </dd>
    </dl>
</section>
