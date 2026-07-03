<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template->name }}</title>
</head>
<body>
    <main>
        <header>
            <p><a href="{{ route('supply.forms.templates.index') }}">Back to templates</a></p>
            <h1>{{ $template->name }}</h1>
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <dl>
            <dt>Code</dt>
            <dd>{{ $template->code }}</dd>
            <dt>Context</dt>
            <dd>{{ $template->context_type instanceof \BackedEnum ? $template->context_type->value : $template->context_type }}</dd>
            <dt>Format</dt>
            <dd>{{ $template->format_type instanceof \BackedEnum ? $template->format_type->value : $template->format_type }}</dd>
        </dl>

        <section>
            <h2>Fields</h2>
            <table>
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Hint</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($template->fields as $field)
                        <tr>
                            <td>{{ $field->field_key }}</td>
                            <td>{{ $field->label }}</td>
                            <td>{{ $field->field_type instanceof \BackedEnum ? $field->field_type->value : $field->field_type }}</td>
                            <td>{{ $field->is_required ? 'Yes' : 'No' }}</td>
                            <td>{{ $field->ai_extraction_hint }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No fields.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h2>Add field</h2>
            <form method="post" action="{{ route('supply.forms.templates.fields.store', $template) }}">
                @csrf
                <label for="field_key">Field key</label>
                <input id="field_key" name="field_key">

                <label for="label">Label</label>
                <input id="label" name="label">

                <label for="field_type">Type</label>
                <select id="field_type" name="field_type">
                    <option value="text">text</option>
                    <option value="decimal">decimal</option>
                    <option value="date">date</option>
                    <option value="sku">sku</option>
                    <option value="currency">currency</option>
                    <option value="textarea">textarea</option>
                </select>

                <label for="is_required">
                    <input id="is_required" name="is_required" type="checkbox" value="1">
                    Required
                </label>

                <label for="ai_extraction_hint">AI extraction hint</label>
                <textarea id="ai_extraction_hint" name="ai_extraction_hint"></textarea>

                <button type="submit">Add field</button>
            </form>
        </section>
    </main>
</body>
</html>
