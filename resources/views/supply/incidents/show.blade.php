@extends('layouts.app')

@section('title')
{{ $incident->incident_number }}
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Incident detail</p>
        <h1>{{ $incident->incident_number }} · {{ $incident->title }}</h1>
        <p>Resolving an incident does not approve the blocked business action.</p>
    </div>
    <nav aria-label="Incident links">
        <a href="{{ route('supply.incidents.index') }}">Back to incidents</a>
        <a href="{{ route('supply.incidents.edit', $incident) }}">Edit</a>
    </nav>
</header>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<section class="guardrail-grid">
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>Status</h2>
            @include('supply.incidents.partials.status-badge', ['label' => $incident->status_label, 'tone' => $incident->status_tone])
        </div>
    </article>
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>Severity</h2>
            @include('supply.incidents.partials.severity-badge', ['label' => $incident->severity_label, 'tone' => $incident->severity_tone])
        </div>
    </article>
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>Priority</h2>
            <p class="text-2xl font-semibold">{{ $incident->priority->value }}</p>
        </div>
    </article>
    <article class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2>SLA</h2>
            @include('supply.incidents.partials.sla-badge', ['label' => $incident->sla_status_label, 'tone' => $incident->sla_tone])
        </div>
    </article>
</section>

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <h2 class="card-title">Workflow blocker</h2>
        <p class="alert alert-warning">Incident management does not perform the workflow action automatically.</p>
        <dl class="grid gap-2 md:grid-cols-3">
            <div>
                <dt class="font-semibold">Type</dt>
                <dd>{{ $incident->incident_type_label }}</dd>
            </div>
            <div>
                <dt class="font-semibold">Source</dt>
                <dd>@include('supply.incidents.partials.workflow-link', ['incident' => $incident])</dd>
            </div>
            <div>
                <dt class="font-semibold">Owner</dt>
                <dd>{{ $incident->assignedUser?->name ?? 'Unassigned' }}</dd>
            </div>
            <div>
                <dt class="font-semibold">Response due</dt>
                <dd>{{ $incident->response_due_at?->format('Y-m-d H:i') ?? 'Not set' }}</dd>
            </div>
            <div>
                <dt class="font-semibold">Resolution due</dt>
                <dd>{{ $incident->resolution_due_at?->format('Y-m-d H:i') ?? 'Not set' }}</dd>
            </div>
            <div>
                <dt class="font-semibold">Occurrences</dt>
                <dd>{{ $incident->occurrence_count }}</dd>
            </div>
            <div class="md:col-span-3">
                <dt class="font-semibold">Description</dt>
                <dd>{{ $incident->description ?? 'No description.' }}</dd>
            </div>
        </dl>
    </div>
</section>

<div class="grid gap-4 xl:grid-cols-2">
    <section class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2 class="card-title">Assign owner</h2>
            <form method="POST" action="{{ route('supply.incidents.assign', $incident) }}" class="grid gap-3">
                @csrf
                <label class="form-control">
                    <span class="label-text">Assigned user</span>
                    <select class="select select-bordered" name="assigned_user_id" required>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($incident->assigned_user_id === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="form-control">
                    <span class="label-text">Reason</span>
                    <input class="input input-bordered" name="reason">
                </label>
                <x-supply.button type="submit">Assign</x-supply.button>
            </form>
        </div>
    </section>

    <section class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2 class="card-title">Change status</h2>
            <p class="text-sm">Critical/high incidents require root cause before closing.</p>
            <form method="POST" action="{{ route('supply.incidents.status', $incident) }}" class="grid gap-3">
                @csrf
                <label class="form-control">
                    <span class="label-text">Status</span>
                    <select class="select select-bordered" name="status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($incident->status->value === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="form-control">
                    <span class="label-text">Resolution note</span>
                    <textarea class="textarea textarea-bordered" name="resolution_note" rows="3"></textarea>
                </label>
                <label class="form-control">
                    <span class="label-text">No-action reason</span>
                    <textarea class="textarea textarea-bordered" name="no_action_required_reason" rows="2"></textarea>
                </label>
                <x-supply.button type="submit">Update status</x-supply.button>
            </form>
        </div>
    </section>
</div>

@include('supply.incidents.partials.root-cause-panel', ['incident' => $incident, 'rootCauseCategories' => $rootCauseCategories])
@include('supply.incidents.partials.corrective-actions', ['incident' => $incident, 'users' => $users])
@include('supply.incidents.partials.escalation-panel', ['incident' => $incident])

<div class="grid gap-4 xl:grid-cols-2">
    <section class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2 class="card-title">Comments</h2>
            <form method="POST" action="{{ route('supply.incidents.comments.store', $incident) }}" class="grid gap-3">
                @csrf
                <textarea class="textarea textarea-bordered" name="comment" rows="3" required></textarea>
                <label class="label cursor-pointer justify-start gap-3">
                    <input class="checkbox" type="checkbox" name="is_internal" value="1" checked>
                    <span>Internal comment</span>
                </label>
                <x-supply.button type="submit">Add comment</x-supply.button>
            </form>
            <div class="divider"></div>
            @forelse ($incident->comments as $comment)
                <article class="border border-base-300 rounded-box p-3">
                    <p>{{ $comment->comment }}</p>
                    <p class="text-sm opacity-70">{{ $comment->user?->name ?? 'System' }} · {{ $comment->created_at?->format('Y-m-d H:i') }}</p>
                </article>
            @empty
                <p>No comments yet.</p>
            @endforelse
        </div>
    </section>

    <section class="card bg-base-100 border border-base-300 shadow-sm">
        <div class="card-body">
            <h2 class="card-title">Timeline</h2>
            @include('supply.incidents.partials.timeline', ['events' => $incident->events])
        </div>
    </section>
</div>
@endsection
