@extends('layouts.app')

@section('title')
Create Incident SLA Policy
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">SLA configuration</p>
        <h1>Create Incident SLA Policy</h1>
    </div>
    <a href="{{ route('supply.incidents.sla-policies.index') }}">Back to SLA policies</a>
</header>

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('supply.incidents.sla-policies.store') }}" class="grid gap-3 md:grid-cols-2">
            @csrf
            <label class="form-control md:col-span-2">
                <span class="label-text">Name</span>
                <input class="input input-bordered" name="name" value="{{ old('name') }}" required>
            </label>
            <label class="form-control">
                <span class="label-text">Company</span>
                <select class="select select-bordered" name="company_id">
                    <option value="">Global</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Incident type</span>
                <select class="select select-bordered" name="incident_type">
                    <option value="">Any</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('incident_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Severity</span>
                <select class="select select-bordered" name="severity">
                    <option value="">Any</option>
                    @foreach ($severities as $severity)
                        <option value="{{ $severity }}" @selected(old('severity') === $severity)>{{ $severity }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Priority</span>
                <select class="select select-bordered" name="priority">
                    <option value="">Any</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority }}" @selected(old('priority') === $priority)>{{ $priority }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Response minutes</span>
                <input class="input input-bordered" type="number" min="1" name="response_minutes" value="{{ old('response_minutes', 60) }}" required>
            </label>
            <label class="form-control">
                <span class="label-text">Resolution minutes</span>
                <input class="input input-bordered" type="number" min="1" name="resolution_minutes" value="{{ old('resolution_minutes', 480) }}" required>
            </label>
            <label class="form-control">
                <span class="label-text">Escalation minutes</span>
                <input class="input input-bordered" type="number" min="1" name="escalation_minutes" value="{{ old('escalation_minutes') }}">
            </label>
            <label class="label cursor-pointer justify-start gap-3">
                <input class="checkbox" type="checkbox" name="is_active" value="1" checked>
                <span>Active</span>
            </label>
            <x-supply.button type="submit" class="md:col-span-2">Create SLA policy</x-supply.button>
        </form>
    </div>
</section>
@endsection
