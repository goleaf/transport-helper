<section>
    <h2>Email workflow</h2>

    @if (! $emailMessage && $canPrepareEmail)
        <form method="post" action="{{ route('supply.supplier-orders.prepare-email', $order) }}">
            @csrf
            <label>
                <input type="hidden" name="auto_export" value="0">
                <input type="checkbox" name="auto_export" value="1" checked>
                Auto-export attachment
            </label>
            <label>
                Attachment format
                <select name="auto_export_format">
                    <option value="excel_csv">Excel spreadsheet</option>
                    <option value="csv">Spreadsheet</option>
                    <option value="json">Structured data</option>
                </select>
            </label>
            <label>
                Language
                <select name="language">
                    <option value="">Supplier default</option>
                    <option value="en">English</option>
                    <option value="lt">Lithuanian</option>
                </select>
            </label>
            <label>
                Subject override
                <input type="text" name="subject" value="{{ old('subject') }}">
            </label>
            <label>
                Body override
                <textarea name="body_text">{{ old('body_text') }}</textarea>
            </label>
            <button type="submit">Prepare email</button>
        </form>
    @endif

    @if ($emailMessage)
        <dl>
            <dt>Recipients</dt>
            <dd>{{ $emailMessage->recipients_text }}</dd>
            <dt>CC</dt>
            <dd>{{ $emailMessage->cc_text }}</dd>
            <dt>Subject</dt>
            <dd>{{ $emailMessage->subject }}</dd>
            <dt>Status</dt>
            <dd><x-supply.status-badge :status="$emailMessage->status" /></dd>
            <dt>Message ID</dt>
            <dd>{{ $emailMessage->message_id }}</dd>
            <dt>Sent at</dt>
            <dd>{{ $emailMessage->sent_at?->toDateTimeString() ?? 'Not sent' }}</dd>
        </dl>

        <h3>Body</h3>
        <div class="message-body">{{ $emailMessage->body_text }}</div>

        <h3>Attachments</h3>
        <ul>
            @forelse ($emailMessage->attachments as $attachment)
                <li>{{ $attachment->original_filename }} ({{ $attachment->mime_type }})</li>
            @empty
                <li>No attachments.</li>
            @endforelse
        </ul>

        @if ($canPrepareEmail && $emailMessage->status !== 'sent')
            <form method="post" action="{{ route('supply.supplier-orders.prepare-email', $order) }}">
                @csrf
                <button type="submit">Regenerate draft</button>
            </form>
        @endif

        @if ($canApproveEmail && $emailMessage->status === 'draft')
            <form method="post" action="{{ route('supply.supplier-orders.approve-email', $order) }}">
                @csrf
                @if ($emailMessage->attachments->isEmpty())
                    <label>
                        <input type="checkbox" name="confirm_no_attachment" value="1">
                        Confirm no attachment
                    </label>
                @endif
                <label>
                    Approval note
                    <textarea name="approval_note">{{ old('approval_note') }}</textarea>
                </label>
                <button type="submit">Approve email</button>
            </form>
        @endif

        @if ($canSendEmail && $emailMessage->status === 'approved')
            <form method="post" action="{{ route('supply.supplier-orders.send-email', $order) }}">
                @csrf
                <label>
                    Sender
                    <select name="sender">
                        <option value="log">Log only</option>
                        <option value="smtp">SMTP placeholder</option>
                        <option value="gmail">Gmail placeholder</option>
                        <option value="microsoft_graph">Microsoft Graph placeholder</option>
                    </select>
                </label>
                <button type="submit">Send email</button>
            </form>
        @endif
    @else
        <p>No draft email has been prepared.</p>
    @endif
</section>
