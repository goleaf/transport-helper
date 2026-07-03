<div class="rounded-lg border border-gray-200 bg-white p-4">
    <h2 class="text-base font-semibold text-gray-900">Dry-Runs</h2>
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach (['import_dry_run','calculation_dry_run','email_dry_run','form_autofill_dry_run','confirmation_dry_run','transport_dry_run','logistics_dry_run','full_uat_dry_run'] as $runType)
            <form method="POST" action="{{ route('supply.pilots.dry-run', [$pilot, $runType]) }}">
                @csrf
                <x-supply.button type="submit" mode="outline" variant="neutral">{{ $runType }}</x-supply.button>
            </form>
        @endforeach
    </div>

    <div class="mt-4 overflow-hidden rounded border border-gray-200">
        <table class="table">
            <thead><tr><th>Run</th><th>Status</th><th>Finished</th></tr></thead>
            <tbody>
                @forelse ($pilot->runs as $run)
                    <tr>
                        <td>{{ $run->run_type }}</td>
                        <td>{{ $run->status }}</td>
                        <td>{{ $run->finished_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-gray-500">No pilot dry-runs recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
