<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Supplier communication</p>
            <h2>Email workflow</h2>
        </div>
    </div>

    @if (! $emailMessage && $canPrepareEmail)
        <form method="post" action="{{ route('supply.supplier-orders.prepare-email', $order) }}">
            @csrf
            <label>
                <input type="hidden" name="auto_export" value="0">
                <input class="checkbox checkbox-primary" type="checkbox" name="auto_export" value="1" checked>
                Attach order spreadsheet
            </label>
            <label>
                Attachment format
                <select class="select select-bordered select-primary" name="auto_export_format">
                    <option value="excel_csv">Excel spreadsheet</option>
                    <option value="csv">Spreadsheet</option>
                </select>
            </label>
            <label>
                Language
                <select class="select select-bordered select-primary" name="language">
                    <option value="">Supplier default</option>
                    <option value="en">English</option>
                    <option value="lt">Lithuanian</option>
                </select>
            </label>
            <label>
                Subject override
                <input class="input input-bordered input-primary" type="text" name="subject" value="{{ old('subject') }}">
            </label>
            <label class="form-field-wide">
                Body override
                <textarea class="textarea textarea-bordered textarea-primary" name="body_text">{{ old('body_text') }}</textarea>
            </label>
            <div class="form-actions">
                <x-supply.button type="submit">Prepare email</x-supply.button>
            </div>
        </form>
    @endif

    @if ($emailMessage)
        <dl>
            <dt>Recipients</dt>
            <dd>{{ $emailMessage->recipients_text }}</dd>
            <dt>CC</dt>
            <dd>{{ $emailMessage->cc_text ?: 'No CC' }}</dd>
            <dt>Subject</dt>
            <dd>{{ $emailMessage->subject }}</dd>
            <dt>Status</dt>
            <dd><x-supply.status-badge :status="$emailMessage->status" /></dd>
            <dt>Message ID</dt>
            <dd>{{ $emailMessage->message_id ?? 'Not assigned' }}</dd>
            <dt>Sent at</dt>
            <dd>{{ $emailMessage->sent_at?->toDateTimeString() ?? 'Not sent' }}</dd>
        </dl>

        <h3>Body</h3>
        <div class="message-body">{{ $emailMessage->body_text }}</div>

        <h3>Attachments</h3>
        <table class="table table-zebra">
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Type</th>
                    <th>Size</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($emailMessage->attachments as $attachment)
                    <tr>
                        <td>{{ $attachment->original_filename }}</td>
                        <td>{{ $attachment->mime_type }}</td>
                        <td>{{ $attachment->size_bytes }} bytes</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">No attachments.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($canPrepareEmail && $emailMessage->status !== 'sent')
            <form method="post" action="{{ route('supply.supplier-orders.prepare-email', $order) }}">
                @csrf
                <div class="form-actions">
                    <x-supply.button type="submit">Regenerate draft</x-supply.button>
                </div>
            </form>
        @endif

        @if ($canApproveEmail && $emailMessage->status === 'draft')
            <form method="post" action="{{ route('supply.supplier-orders.approve-email', $order) }}">
                @csrf
                @if ($emailMessage->attachments->isEmpty())
                    <label>
                        <input class="checkbox checkbox-primary" type="checkbox" name="confirm_no_attachment" value="1">
                        Confirm no attachment
                    </label>
                @endif
                <label class="form-field-wide">
                    Approval note
                    <textarea class="textarea textarea-bordered textarea-primary" name="approval_note">{{ old('approval_note') }}</textarea>
                </label>
                <div class="form-actions">
                    <x-supply.button type="submit">Approve email</x-supply.button>
                </div>
            </form>
        @endif

        @if ($canSendEmail && $emailMessage->status === 'approved')
            <form method="post" action="{{ route('supply.supplier-orders.send-email', $order) }}">
                @csrf
                <label>
                    Sender
                    <select class="select select-bordered select-primary" name="sender">
                        <option value="log">Log only</option>
                        <option value="smtp">SMTP placeholder</option>
                        <option value="gmail">Gmail placeholder</option>
                        <option value="microsoft_graph">Microsoft Graph placeholder</option>
                    </select>
                </label>
                <div class="form-actions">
                    <x-supply.button type="submit">Send email</x-supply.button>
                </div>
            </form>
        @endif
    @else
        <x-supply.empty-state title="No email draft">Prepare a supplier email after the order is approved.</x-supply.empty-state>
    @endif
</section>
