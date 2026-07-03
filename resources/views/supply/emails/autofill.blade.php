<header>
            <p><a href="{{ route('supply.emails.show', $email) }}">Back to email</a></p>
            <h1>Autofill Form From Email</h1>
        </header>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <section>
            <h2>Email</h2>
            <dl>
                <dt>Subject</dt>
                <dd>{{ $email->subject }}</dd>
                <dt>Sender</dt>
                <dd>{{ $email->from_email }}</dd>
                <dt>Received date</dt>
                <dd>{{ $email->received_at?->toDateTimeString() }}</dd>
                <dt>Detected supplier</dt>
                <dd>{{ $email->relatedSupplier?->name }}</dd>
                <dt>Possible supplier order</dt>
                <dd>{{ $email->relatedSupplierOrder?->order_number }}</dd>
            </dl>
            <p>{{ $email->body_preview }}</p>
        </section>

        <form method="post" action="{{ route('supply.emails.autofill.preview', $email) }}">
            @csrf
            <label for="form_template_id">Form template</label>
            <select id="form_template_id" name="form_template_id">
                @forelse ($templates as $template)
                    <option value="{{ $template->id }}">{{ $template->autofill_option_label }}</option>
                @empty
                    <option value="">No active templates</option>
                @endforelse
            </select>

            <label for="extractor">Extractor</label>
            <select id="extractor" name="extractor">
                <option value="rule_based">Rule based</option>
                <option value="fake">Fake</option>
                <option value="external">External placeholder</option>
            </select>

            <label>
                <input type="checkbox" name="force_new" value="1">
                Force new run
            </label>

            <label>
                <input type="checkbox" name="include_attachments_summary" value="1" checked>
                Include attachments summary
            </label>

            <button type="submit">Generate autofill preview</button>
        </form>
