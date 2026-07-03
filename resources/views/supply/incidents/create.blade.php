@extends('layouts.app')

@section('title')
Create Incident
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Manual exception capture</p>
        <h1>Create Incident</h1>
        <p>Resolving an incident does not approve the blocked business action.</p>
    </div>
    <a href="{{ route('supply.incidents.index') }}">Back to incidents</a>
</header>

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('supply.incidents.store') }}" class="grid gap-3 md:grid-cols-2">
            @csrf
            <label class="form-control">
                <span class="label-text">Company</span>
                <select class="select select-bordered" name="company_id">
                    <option value="">No company</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Assigned user</span>
                <select class="select select-bordered" name="assigned_user_id">
                    <option value="">Auto assign</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('assigned_user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Type</span>
                <select class="select select-bordered" name="incident_type" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('incident_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Severity</span>
                <select class="select select-bordered" name="severity">
                    <option value="">Auto</option>
                    @foreach ($severities as $severity)
                        <option value="{{ $severity }}" @selected(old('severity') === $severity)>{{ $severity }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Priority</span>
                <select class="select select-bordered" name="priority">
                    <option value="">Auto</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority }}" @selected(old('priority') === $priority)>{{ $priority }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Source type</span>
                <select class="select select-bordered" name="source_type">
                    <option value="">Manual</option>
                    @foreach ($sourceTypes as $sourceType)
                        <option value="{{ $sourceType }}" @selected(old('source_type') === $sourceType)>{{ $sourceType }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Source ID</span>
                <input class="input input-bordered" type="number" name="source_id" value="{{ old('source_id') }}">
            </label>
            <label class="form-control">
                <span class="label-text">Title</span>
                <input class="input input-bordered" name="title" value="{{ old('title') }}" required>
            </label>
            <label class="form-control md:col-span-2">
                <span class="label-text">Description</span>
                <textarea class="textarea textarea-bordered" name="description" rows="5">{{ old('description') }}</textarea>
            </label>
            <x-supply.button type="submit" class="md:col-span-2">Create incident</x-supply.button>
        </form>
    </div>
</section>
@endsection
