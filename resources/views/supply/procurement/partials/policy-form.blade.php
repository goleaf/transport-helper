<label>
    <span>Company</span>
    <select class="select select-bordered" name="company_id" required>
        @forelse ($companies as $company)
            <option value="{{ $company->id }}" @selected((string) old('company_id', $policy?->company_id) === (string) $company->id)>{{ $company->name }}</option>
        @empty
            <option value="">No companies</option>
        @endforelse
    </select>
    @error('company_id')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Name</span>
    <input class="input input-bordered" name="name" value="{{ old('name', $policy?->name) }}" required>
    @error('name')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Status</span>
    <select class="select select-bordered" name="status">
        @forelse ($statuses as $status)
            <option value="{{ $status }}" @selected(old('status', $policy?->status?->value ?? 'active') === $status)>{{ ucfirst($status) }}</option>
        @empty
            <option value="active">Active</option>
        @endforelse
    </select>
</label>

<label>
    <span>Enforcement mode</span>
    <select class="select select-bordered" name="enforcement_mode" required>
        @forelse ($modes as $mode)
            <option value="{{ $mode }}" @selected(old('enforcement_mode', $policy?->enforcement_mode?->value ?? 'advisory') === $mode)>{{ ucfirst($mode) }}</option>
        @empty
            <option value="advisory">Advisory</option>
        @endforelse
    </select>
    @error('enforcement_mode')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Default currency</span>
    <input class="input input-bordered" name="default_currency" maxlength="3" value="{{ old('default_currency', $policy?->default_currency ?? 'EUR') }}" required>
    @error('default_currency')<span>{{ $message }}</span>@enderror
</label>

<label>
    <input type="hidden" name="is_default" value="0">
    <input class="checkbox" type="checkbox" name="is_default" value="1" @checked(old('is_default', $policy?->is_default ?? false))>
    <span>Company default policy</span>
</label>
