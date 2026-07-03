<section>
    <h2>Field Review</h2>
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Field</th>
                <th>Extracted value</th>
                <th>Normalized value</th>
                <th>Final value</th>
                <th>Confidence</th>
                <th>Source excerpt</th>
                <th>Review</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>
                        {{ $row['label'] }}
                        <br>
                        <small>{{ $row['field_key'] }} {{ $row['field_type'] }}</small>
                    </td>
                    <td><x-supply.structured-value :value="$row['field']->extracted_value" /></td>
                    <td><x-supply.structured-value :value="$row['field']->normalized_value" /></td>
                    <td><x-supply.structured-value :value="$row['field']->final_value" /></td>
                    <td>{{ $row['field']->confidence }}</td>
                    <td>{{ $row['field']->source_excerpt }}</td>
                    <td>{{ $row['review_text'] }}</td>
                    <td>@include('supply.form-autofill-runs.partials.review-actions', ['run' => $run, 'field' => $row['field'], 'finalValue' => $row['final_input_value']])</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No fields.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
