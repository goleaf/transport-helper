<div>
    <label class="block text-sm font-medium text-gray-700" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="select select-bordered select-primary mt-1 w-full">
        @forelse ($companies as $company)
            <option value="{{ $company->id }}" @selected(old('company_id', $formValues['company_id']) == $company->id)>{{ $company->name }}</option>
        @empty
            <option value="">No companies available</option>
        @endforelse
    </select>
    @error('company_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-gray-700" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $formValues['name']) }}" class="input input-bordered input-primary mt-1 w-full">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="provider">Provider</label>
        <input id="provider" name="provider" value="{{ old('provider', $formValues['provider']) }}" class="input input-bordered input-primary mt-1 w-full">
        @error('provider') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="type">Type</label>
        <input id="type" name="type" value="{{ old('type', $formValues['type']) }}" class="input input-bordered input-primary mt-1 w-full">
        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="environment">Environment</label>
        <input id="environment" name="environment" value="{{ old('environment', $formValues['environment']) }}" class="input input-bordered input-primary mt-1 w-full">
        @error('environment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="is_external" value="1" class="checkbox checkbox-primary" @checked(old('is_external', $formValues['is_external']))>
        External integration
    </label>
    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="requires_approval" value="1" class="checkbox checkbox-primary" @checked(old('requires_approval', $formValues['requires_approval']))>
        Requires approval
    </label>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700" for="config_json">Config JSON</label>
    <textarea id="config_json" name="config_json" rows="8" class="textarea textarea-bordered textarea-primary mt-1 w-full font-mono text-sm">{{ old('config_json', $configText) }}</textarea>
    <p class="mt-1 text-xs text-gray-500">Secrets are encrypted at rest and masked after save.</p>
    @error('config') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700" for="notes">Notes</label>
    <textarea id="notes" name="notes" rows="3" class="textarea textarea-bordered textarea-primary mt-1 w-full">{{ old('notes', $formValues['notes']) }}</textarea>
</div>
