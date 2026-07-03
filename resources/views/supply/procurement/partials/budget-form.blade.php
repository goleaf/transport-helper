<label>
    <span>Company</span>
    <select class="select select-bordered" name="company_id" required>
        @forelse ($companies as $company)
            <option value="{{ $company->id }}" @selected((string) old('company_id', $budget?->company_id) === (string) $company->id)>{{ $company->name }}</option>
        @empty
            <option value="">No companies</option>
        @endforelse
    </select>
    @error('company_id')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Name</span>
    <input class="input input-bordered" name="name" value="{{ old('name', $budget?->name) }}" required>
    @error('name')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Period type</span>
    <select class="select select-bordered" name="period_type" required>
        @forelse ($periodTypes as $periodType)
            <option value="{{ $periodType }}" @selected(old('period_type', $budget?->period_type?->value ?? 'monthly') === $periodType)>{{ ucfirst($periodType) }}</option>
        @empty
            <option value="monthly">Monthly</option>
        @endforelse
    </select>
</label>

<label>
    <span>Date from</span>
    <input class="input input-bordered" type="date" name="date_from" value="{{ old('date_from', $budget?->date_from?->toDateString()) }}" required>
    @error('date_from')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Date to</span>
    <input class="input input-bordered" type="date" name="date_to" value="{{ old('date_to', $budget?->date_to?->toDateString()) }}" required>
    @error('date_to')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Currency</span>
    <input class="input input-bordered" name="currency" maxlength="3" value="{{ old('currency', $budget?->currency ?? 'EUR') }}" required>
</label>

<label>
    <span>Total amount</span>
    <input class="input input-bordered" type="number" step="0.0001" min="0" name="total_amount" value="{{ old('total_amount', $budget?->total_amount) }}" required>
    @error('total_amount')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Status</span>
    <select class="select select-bordered" name="status">
        @forelse ($statuses as $status)
            <option value="{{ $status }}" @selected(old('status', $budget?->status?->value ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
        @empty
            <option value="draft">Draft</option>
        @endforelse
    </select>
</label>

<label>
    <span>Owner</span>
    <select class="select select-bordered" name="owner_user_id">
        <option value="">No owner</option>
        @forelse ($users as $user)
            <option value="{{ $user->id }}" @selected((string) old('owner_user_id', $budget?->owner_user_id) === (string) $user->id)>{{ $user->name }}</option>
        @empty
            <option value="">No users</option>
        @endforelse
    </select>
</label>

<label>
    <span>Notes</span>
    <textarea class="textarea textarea-bordered" name="notes">{{ old('notes', $budget?->notes) }}</textarea>
</label>
