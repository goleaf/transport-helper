<section>
    <h2>Validation And Export</h2>
    <form method="post" action="{{ route('supply.form-autofill-runs.validate', $run) }}">
        @csrf
        <label>
            <input type="checkbox" name="ignore_optional_review" value="1">
            Ignore optional review fields
        </label>
        <label>
            <input type="checkbox" name="mismatch_reviewed" value="1">
            Quantity mismatch reviewed
        </label>
        <input name="validation_note" placeholder="Validation note">
        <button type="submit">Validate run</button>
    </form>

    <form method="post" action="{{ route('supply.form-autofill-runs.export', $run) }}">
        @csrf
        <select name="format">
            <option value="json">Structured data</option>
            <option value="csv">Spreadsheet</option>
        </select>
        <label>
            <input type="checkbox" name="include_review_fields" value="1">
            Include review fields
        </label>
        <button type="submit">Export</button>
    </form>

    <h3>Outputs</h3>
    <ul>
        @forelse ($run->outputs as $output)
            <li>
                <x-supply.human-label :value="$output->output_type" /> {{ $output->filename }}
                @if ($output->stored_path)
                    <a href="{{ route('supply.form-autofill-outputs.download', $output) }}">Download</a>
                @endif
            </li>
        @empty
            <li>No outputs.</li>
        @endforelse
    </ul>
</section>
