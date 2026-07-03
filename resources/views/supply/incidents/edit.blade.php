@extends('layouts.app')

@section('title')
Edit Incident
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Incident update</p>
        <h1>Edit {{ $incident->incident_number }}</h1>
    </div>
    <a href="{{ route('supply.incidents.show', $incident) }}">Back to incident</a>
</header>

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('supply.incidents.update', $incident) }}" class="grid gap-3 md:grid-cols-2">
            @csrf
            @method('PATCH')
            <label class="form-control">
                <span class="label-text">Severity</span>
                <select class="select select-bordered" name="severity">
                    @foreach ($severities as $severity)
                        <option value="{{ $severity }}" @selected($incident->severity->value === $severity)>{{ $severity }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Priority</span>
                <select class="select select-bordered" name="priority">
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority }}" @selected($incident->priority->value === $priority)>{{ $priority }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Assigned user</span>
                <select class="select select-bordered" name="assigned_user_id">
                    <option value="">Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected($incident->assigned_user_id === $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control md:col-span-2">
                <span class="label-text">Title</span>
                <input class="input input-bordered" name="title" value="{{ old('title', $incident->title) }}">
            </label>
            <label class="form-control md:col-span-2">
                <span class="label-text">Description</span>
                <textarea class="textarea textarea-bordered" name="description" rows="5">{{ old('description', $incident->description) }}</textarea>
            </label>
            <x-supply.button type="submit" class="md:col-span-2">Save incident</x-supply.button>
        </form>
    </div>
</section>
@endsection
