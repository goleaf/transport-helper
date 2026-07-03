@props(['incident', 'users'])

<section class="card bg-base-100 border border-base-300 shadow-sm">
    <div class="card-body">
        <h2 class="card-title">Corrective actions</h2>

        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Owner</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incident->correctiveActions as $action)
                        <tr>
                            <td>{{ $action->title }}</td>
                            <td>{{ $action->owner?->name ?? 'Unassigned' }}</td>
                            <td>{{ $action->due_date?->format('Y-m-d') ?? 'Not set' }}</td>
                            <td>{{ $action->status_label }}</td>
                            <td>
                                <form method="POST" action="{{ route('supply.incidents.corrective-actions.done', [$incident, $action]) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="completion_note" value="Completed from incident detail.">
                                    <x-supply.button type="submit" size="sm">Mark done</x-supply.button>
                                </form>
                                <form method="POST" action="{{ route('supply.incidents.corrective-actions.verify', [$incident, $action]) }}" class="inline">
                                    @csrf
                                    <x-supply.button type="submit" size="sm" mode="outline">Verify</x-supply.button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No corrective actions recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('supply.incidents.corrective-actions.store', $incident) }}" class="grid gap-3 md:grid-cols-2">
            @csrf
            <label class="form-control">
                <span class="label-text">Title</span>
                <input class="input input-bordered" name="title" required>
            </label>
            <label class="form-control">
                <span class="label-text">Owner</span>
                <select class="select select-bordered" name="owner_user_id">
                    <option value="">Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-control">
                <span class="label-text">Due date</span>
                <input class="input input-bordered" type="date" name="due_date">
            </label>
            <label class="form-control md:col-span-2">
                <span class="label-text">Description</span>
                <textarea class="textarea textarea-bordered" name="description" rows="3"></textarea>
            </label>
            <x-supply.button type="submit" class="md:col-span-2">Add corrective action</x-supply.button>
        </form>
    </div>
</section>
