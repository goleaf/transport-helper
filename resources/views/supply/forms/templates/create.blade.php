<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Form Template</title>
</head>
<body>
    <main>
        <header>
            <p><a href="{{ route('supply.forms.templates.index') }}">Back to templates</a></p>
            <h1>Create Form Template</h1>
        </header>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('supply.forms.templates.store') }}">
            @csrf
            <label for="company_id">Company</label>
            <select id="company_id" name="company_id">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>

            <label for="name">Name</label>
            <input id="name" name="name" value="{{ old('name') }}">

            <label for="code">Code</label>
            <input id="code" name="code" value="{{ old('code') }}">

            <label for="context_type">Context</label>
            <select id="context_type" name="context_type">
                <option value="supplier_confirmation">supplier_confirmation</option>
                <option value="carrier_quote">carrier_quote</option>
                <option value="logistics_update">logistics_update</option>
                <option value="ready_date_update">ready_date_update</option>
                <option value="quantity_mismatch">quantity_mismatch</option>
                <option value="custom_email_form">custom_email_form</option>
            </select>

            <input type="hidden" name="format_type" value="internal_html">
            <input type="hidden" name="version" value="1.0">
            <input type="hidden" name="is_active" value="1">

            <button type="submit">Create template</button>
        </form>
    </main>
</body>
</html>
