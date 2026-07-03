<header class="detail-header">
    <div class="detail-title">
        <p><a href="{{ route('supply.forms.templates.index') }}">Back to templates</a></p>
        <h1>{{ $template->name }}</h1>
        <div class="mini-pills" aria-label="Template status">
            <span>{{ $template->code }}</span>
            <x-supply.status-badge :status="$statusValue" />
        </div>
    </div>

    <div class="page-actions">
        <x-supply.button :href="route('supply.forms.templates.edit', $template)" variant="neutral" mode="outline">Edit template</x-supply.button>
    </div>
</header>

@if (session('status'))
    <p role="status" class="alert alert-info">{{ session('status') }}</p>
@endif

@if ($errors->any())
    <section role="alert" class="alert alert-error" aria-labelledby="template-errors-title">
        <h2 id="template-errors-title">Fix these fields</h2>
        <ul>
            @forelse ($errors->all() as $error)
                <li>{{ $error }}</li>
            @empty
                <li>No validation errors.</li>
            @endforelse
        </ul>
    </section>
@endif

<section class="grid" aria-label="Template summary">
    <div class="stat metric"><span class="stat-title">Fields</span><strong class="stat-value">{{ $fieldCount }}</strong></div>
    <div class="stat metric"><span class="stat-title">Required</span><strong class="stat-value">{{ $requiredFieldCount }}</strong></div>
    <div class="stat metric"><span class="stat-title">Optional</span><strong class="stat-value">{{ $optionalFieldCount }}</strong></div>
    <div class="stat metric"><span class="stat-title">Autofill runs</span><strong class="stat-value">{{ $autofillRunCount }}</strong></div>
</section>

<section>
    <div class="section-heading">
        <div>
            <h2>Template Profile</h2>
            <p class="section-intro">Scope, format and ownership for this extraction template.</p>
        </div>
    </div>

    <dl>
        <dt>Code</dt>
        <dd>{{ $template->code }}</dd>
        <dt>Version</dt>
        <dd>{{ $template->version }}</dd>
        <dt>Status</dt>
        <dd><x-supply.status-badge :status="$statusValue" /></dd>
        <dt>Context</dt>
        <dd>{{ $contextLabel }}</dd>
        <dt>Format</dt>
        <dd>{{ $formatLabel }}</dd>
        <dt>Company</dt>
        <dd>{{ $companyName }}</dd>
        <dt>Supplier</dt>
        <dd>{{ $supplierName }}</dd>
        <dt>Carrier</dt>
        <dd>{{ $carrierName }}</dd>
    </dl>
</section>

<section>
    <div class="section-heading">
        <div>
            <h2>Fields</h2>
            <p class="section-intro">Fields are applied in sort order and reviewed by humans before business changes are applied.</p>
        </div>
    </div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Sort</th>
                <th>Key</th>
                <th>Label</th>
                <th>Type</th>
                <th>Requirement</th>
                <th>Extraction hint</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($fieldRows as $field)
                <tr>
                    <td>{{ $field['sort_order'] }}</td>
                    <td><code class="inline-code">{{ $field['key'] }}</code></td>
                    <td>{{ $field['label'] }}</td>
                    <td>{{ $field['type_label'] }}</td>
                    <td><x-supply.status-badge :status="$field['requirement_status']" /></td>
                    <td class="muted-cell">{{ $field['hint'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No fields yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section>
    <div class="section-heading">
        <div>
            <h2>Add Field</h2>
            <p class="section-intro">Add only fields that can be reviewed and approved by a human operator.</p>
        </div>
    </div>

    <form method="post" action="{{ route('supply.forms.templates.fields.store', $template) }}" class="template-field-form">
        @csrf
        <label for="field_key">
            Field key
            <input class="input input-bordered input-primary" id="field_key" name="field_key" value="{{ old('field_key') }}" autocomplete="off" aria-describedby="field_key_help field_key_error">
            <span id="field_key_help" class="form-help">Use a stable machine key such as supplier_reference or ready_date.</span>
            @error('field_key')
                <span id="field_key_error" class="form-error">{{ $message }}</span>
            @enderror
        </label>

        <label for="label">
            Label
            <input class="input input-bordered input-primary" id="label" name="label" value="{{ old('label') }}" autocomplete="off" aria-describedby="label_error">
            @error('label')
                <span id="label_error" class="form-error">{{ $message }}</span>
            @enderror
        </label>

        <label for="field_type">
            Type
            <select class="select select-bordered select-primary" id="field_type" name="field_type" aria-describedby="field_type_error">
                @forelse ($fieldTypeOptions as $fieldType)
                    <option value="{{ $fieldType['value'] }}" @selected(old('field_type', 'text') === $fieldType['value'])>{{ $fieldType['label'] }}</option>
                @empty
                    <option value="text">Text</option>
                @endforelse
            </select>
            @error('field_type')
                <span id="field_type_error" class="form-error">{{ $message }}</span>
            @enderror
        </label>

        <label for="sort_order">
            Sort order
            <input class="input input-bordered input-primary" id="sort_order" name="sort_order" inputmode="numeric" value="{{ old('sort_order', $nextSortOrder) }}" aria-describedby="sort_order_error">
            @error('sort_order')
                <span id="sort_order_error" class="form-error">{{ $message }}</span>
            @enderror
        </label>

        <label class="checkbox-field" for="is_required">
            <input type="hidden" name="is_required" value="0">
            <input class="checkbox checkbox-primary" id="is_required" name="is_required" type="checkbox" value="1" @checked(old('is_required'))>
            Required field
        </label>

        <label class="form-field-wide" for="ai_extraction_hint">
            AI extraction hint
            <textarea class="textarea textarea-bordered textarea-primary" id="ai_extraction_hint" name="ai_extraction_hint" rows="4" aria-describedby="ai_extraction_hint_help ai_extraction_hint_error">{{ old('ai_extraction_hint') }}</textarea>
            <span id="ai_extraction_hint_help" class="form-help">Describe the exact evidence the AI may use. This is a suggestion only; application still requires human approval.</span>
            @error('ai_extraction_hint')
                <span id="ai_extraction_hint_error" class="form-error">{{ $message }}</span>
            @enderror
        </label>

        <div class="form-actions form-field-wide">
            <x-supply.button type="submit">Add field</x-supply.button>
        </div>
    </form>
</section>
