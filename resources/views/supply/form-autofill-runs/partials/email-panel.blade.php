<section>
    <h2>Original email</h2>
    <dl>
        <dt>Subject</dt>
        <dd>{{ $run->emailMessage?->subject }}</dd>
        <dt>From</dt>
        <dd>{{ $run->emailMessage?->from_email }}</dd>
        <dt>Received at</dt>
        <dd>{{ $run->emailMessage?->received_at?->toDateTimeString() }}</dd>
        <dt>Supplier</dt>
        <dd>{{ $run->emailMessage?->relatedSupplier?->name }}</dd>
        <dt>Supplier order</dt>
        <dd>{{ $run->emailMessage?->relatedSupplierOrder?->order_number }}</dd>
    </dl>
    <pre>{{ $run->emailMessage?->body_text }}</pre>

    <h3>Attachments</h3>
    <ul>
        @forelse ($run->emailMessage?->attachments ?? [] as $attachment)
            <li>{{ $attachment->original_filename }} {{ $attachment->mime_type }} {{ $attachment->size_bytes }}</li>
        @empty
            <li>No attachments.</li>
        @endforelse
    </ul>
</section>
