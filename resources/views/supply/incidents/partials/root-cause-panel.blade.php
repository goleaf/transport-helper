@props(['incident', 'rootCauseCategories'])

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <h2 class="card-title">Root cause analysis</h2>
        <p class="text-sm">Critical/high incidents require root cause before closing.</p>

        <dl class="grid gap-2 md:grid-cols-2">
            <div>
                <dt class="font-semibold">Category</dt>
                <dd>{{ $incident->root_cause_category_label }}</dd>
            </div>
            <div>
                <dt class="font-semibold">Corrective action required</dt>
                <dd>{{ $incident->corrective_action_required ? 'Yes' : 'No' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="font-semibold">Summary</dt>
                <dd>{{ $incident->root_cause_summary ?? 'Not set' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="font-semibold">Prevention notes</dt>
                <dd>{{ $incident->prevention_notes ?? 'Not set' }}</dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('supply.incidents.root-cause.store', $incident) }}" class="grid gap-3">
            @csrf
            <label class="form-control">
                <span class="label-text">Root cause category</span>
                <select class="select select-bordered" name="root_cause_category" required>
                    @foreach ($rootCauseCategories as $category)
                        <option value="{{ $category }}" @selected($incident->root_cause_category === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Root cause summary</span>
                <textarea class="textarea textarea-bordered" name="root_cause_summary" rows="3" required>{{ old('root_cause_summary', $incident->root_cause_summary) }}</textarea>
            </label>
            <label class="form-control">
                <span class="label-text">Prevention notes</span>
                <textarea class="textarea textarea-bordered" name="prevention_notes" rows="2">{{ old('prevention_notes', $incident->prevention_notes) }}</textarea>
            </label>
            <label class="label cursor-pointer justify-start gap-3">
                <input class="checkbox" type="checkbox" name="corrective_action_required" value="1" @checked($incident->corrective_action_required)>
                <span>Corrective action required</span>
            </label>
            <label class="form-control">
                <span class="label-text">No-action reason</span>
                <textarea class="textarea textarea-bordered" name="no_action_required_reason" rows="2">{{ old('no_action_required_reason', $incident->no_action_required_reason) }}</textarea>
            </label>
            <x-supply.button type="submit">Save root cause</x-supply.button>
        </form>
    </div>
</section>
