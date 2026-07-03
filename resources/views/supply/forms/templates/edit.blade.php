@extends('layouts.app')

@section('title')
Edit Form Template
@endsection

@section('content')
<h1>Edit Form Template</h1>

<form method="post" action="{{ route('supply.forms.templates.update', $template) }}">
    @csrf
    @method('patch')
    <input type="hidden" name="company_id" value="{{ $template->company_id }}">
    <label>Name <input name="name" value="{{ old('name', $template->name) }}"></label>
    <label>Code <input name="code" value="{{ old('code', $template->code) }}"></label>
    <label>Context <input name="context_type" value="{{ old('context_type', $template->context_type_value) }}"></label>
    <label>Format <input name="format_type" value="{{ old('format_type', $template->format_type_value) }}"></label>
    <label>Version <input name="version" value="{{ old('version', $template->version) }}"></label>
    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))> Active</label>
    <button type="submit">Update template</button>
</form>
@endsection
