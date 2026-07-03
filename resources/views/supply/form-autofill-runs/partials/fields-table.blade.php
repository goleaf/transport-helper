<section>
    <h2>Field Review</h2>
    <table>
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
            @forelse ($run->fieldValues as $field)
                @php($templateField = $run->formTemplate?->fields->firstWhere('field_key', $field->field_key))
                @php($extractedValue = is_scalar($field->extracted_value) || $field->extracted_value === null ? $field->extracted_value : json_encode($field->extracted_value))
                @php($normalizedValue = is_scalar($field->normalized_value) || $field->normalized_value === null ? $field->normalized_value : json_encode($field->normalized_value))
                @php($finalValue = is_scalar($field->final_value) || $field->final_value === null ? $field->final_value : json_encode($field->final_value))
                <tr>
                    <td>
                        {{ $templateField?->label ?? $field->field_key }}
                        <br>
                        <small>{{ $field->field_key }} {{ $templateField?->field_type instanceof \BackedEnum ? $templateField->field_type->value : $templateField?->field_type }}</small>
                    </td>
                    <td>{{ $extractedValue }}</td>
                    <td>{{ $normalizedValue }}</td>
                    <td>{{ $finalValue }}</td>
                    <td>{{ $field->confidence }}</td>
                    <td>{{ $field->source_excerpt }}</td>
                    <td>{{ $field->requires_review ? 'Needs review' : 'Resolved' }} {{ $field->review_reason }}</td>
                    <td>@include('supply.form-autofill-runs.partials.review-actions', ['run' => $run, 'field' => $field, 'finalValue' => $finalValue])</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No fields.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
