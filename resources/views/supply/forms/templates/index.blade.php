@extends('layouts.app')

@section('title')
Form Templates
@endsection

@section('content')
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
                <td><x-supply.human-label :value="$template->context_type" /></td>
                <td><x-supply.human-label :value="$template->format_type" /></td>
                <td>{{ $template->version }}</td>
                <td>{{ $template->fields_count }}</td>
                <td>{{ $template->is_active ? 'Yes' : 'No' }}</td>
                <td><x-supply.table-action :href="route('supply.forms.templates.show', $template)" label="Open" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="8">No templates yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $templates->links() }}
@endsection
