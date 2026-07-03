<label>Company
    <select class="select select-bordered select-primary" name="company_id">
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" @selected((string) old('company_id', $carrier?->company_id) === (string) $company->id)>{{ $company->name }}</option>
        @endforeach
    </select>
</label>
<label>Name <input class="input input-bordered input-primary" name="name" value="{{ old('name', $carrier?->name) }}"></label>
<label>Code <input class="input input-bordered input-primary" name="code" value="{{ old('code', $carrier?->code) }}"></label>
<label>Default currency <input class="input input-bordered input-primary" name="default_currency" value="{{ old('default_currency', $carrier?->default_currency ?? 'EUR') }}"></label>
<label>Reliability score <input class="input input-bordered input-primary" name="reliability_score" inputmode="decimal" value="{{ old('reliability_score', $carrier?->reliability_score) }}"></label>
<label><input class="checkbox checkbox-primary" type="checkbox" name="is_active" value="1" @checked(old('is_active', $carrier?->is_active ?? true))> Active</label>
<label>Notes <textarea class="textarea textarea-bordered textarea-primary" name="notes">{{ old('notes', $carrier?->notes) }}</textarea></label>
