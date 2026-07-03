<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autofill Form From Email</title>
</head>
<body>
    <main>
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
        </section>

        <form method="post" action="{{ route('supply.emails.autofill.preview', $email) }}">
            @csrf
            <label for="form_template_id">Form template</label>
            <select id="form_template_id" name="form_template_id">
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}">{{ $template->name }} {{ $template->context_type instanceof \BackedEnum ? $template->context_type->value : $template->context_type }}</option>
                @endforeach
            </select>

            <button type="submit">Generate autofill preview</button>
        </form>
    </main>
</body>
</html>
