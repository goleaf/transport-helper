<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Templates</title>
</head>
<body>
    <main>
        <header>
            <h1>Form Templates</h1>
            <a href="{{ route('supply.forms.templates.create') }}">Create template</a>
        </header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Context</th>
                    <th>Format</th>
                    <th>Version</th>
                    <th>Fields</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($templates as $template)
                    <tr>
                        <td>{{ $template->name }}</td>
                        <td>{{ $template->code }}</td>
                        <td>{{ $template->context_type instanceof \BackedEnum ? $template->context_type->value : $template->context_type }}</td>
                        <td>{{ $template->format_type instanceof \BackedEnum ? $template->format_type->value : $template->format_type }}</td>
                        <td>{{ $template->version }}</td>
                        <td>{{ $template->fields_count }}</td>
                        <td>{{ $template->is_active ? 'Yes' : 'No' }}</td>
                        <td><a href="{{ route('supply.forms.templates.show', $template) }}">Open</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No templates yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $templates->links() }}
    </main>
</body>
</html>
