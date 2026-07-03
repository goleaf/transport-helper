<div>
    <label class="block text-sm font-medium text-gray-700" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="select select-bordered select-primary mt-1 w-full" @if($pilot) disabled @endif>
        @forelse ($companies as $company)
            <option value="{{ $company->id }}" @selected(old('company_id', $pilot?->company_id) == $company->id)>{{ $company->name }}</option>
        @empty
            <option value="">No companies available</option>
        @endforelse
    </select>
    @if($pilot)<input type="hidden" name="company_id" value="{{ $pilot->company_id }}">@endif
    @error('company_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700" for="supplier_id">Supplier</label>
    <select id="supplier_id" name="supplier_id" class="select select-bordered select-primary mt-1 w-full" @if($pilot) disabled @endif>
        @forelse ($suppliers as $supplier)
            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $pilot?->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
        @empty
            <option value="">No suppliers available</option>
        @endforelse
    </select>
    @if($pilot)<input type="hidden" name="supplier_id" value="{{ $pilot->supplier_id }}">@endif
    @error('supplier_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700" for="name">Pilot name</label>
    <input id="name" name="name" class="input input-bordered input-primary mt-1 w-full" value="{{ old('name', $pilot?->name) }}">
    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700" for="description">Description</label>
    <textarea id="description" name="description" rows="3" class="textarea textarea-bordered textarea-primary mt-1 w-full">{{ old('description', $pilot?->description) }}</textarea>
    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
