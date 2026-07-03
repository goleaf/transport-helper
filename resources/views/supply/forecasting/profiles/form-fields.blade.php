<label>
    <span>Company</span>
    <select class="select select-bordered" name="company_id" required>
        @forelse ($companies as $company)
            <option value="{{ $company->id }}" @selected((string) old('company_id', $profile?->company_id) === (string) $company->id)>{{ $company->name }}</option>
        @empty
            <option value="">No companies</option>
        @endforelse
    </select>
    @error('company_id')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Name</span>
    <input class="input input-bordered" name="name" value="{{ old('name', $profile?->name) }}" required>
    @error('name')<span>{{ $message }}</span>@enderror
</label>

<label>
    <span>Supplier</span>
    <select class="select select-bordered" name="supplier_id">
        <option value="">Any supplier</option>
        @forelse ($suppliers as $supplier)
            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $profile?->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
        @empty
            <option value="">No suppliers</option>
        @endforelse
    </select>
</label>

<label>
    <span>Product</span>
    <select class="select select-bordered" name="product_id">
        <option value="">Any product</option>
        @forelse ($products as $product)
            <option value="{{ $product->id }}" @selected((string) old('product_id', $profile?->product_id) === (string) $product->id)>{{ $product->sku }} - {{ $product->name }}</option>
        @empty
            <option value="">No products</option>
        @endforelse
    </select>
</label>

<label>
    <span>Category</span>
    <input class="input input-bordered" name="category" value="{{ old('category', $profile?->category) }}">
</label>

<label>
    <span>Priority</span>
    <input class="input input-bordered" type="number" name="priority" min="1" max="10000" value="{{ old('priority', $profile?->priority ?? 100) }}">
</label>

<label>
    <span>Lead time days override</span>
    <input class="input input-bordered" type="number" name="lead_time_days_override" min="0" value="{{ old('lead_time_days_override', $profile?->lead_time_days_override) }}">
</label>

<label>
    <span>Safety days override</span>
    <input class="input input-bordered" type="number" name="safety_days_override" min="0" value="{{ old('safety_days_override', $profile?->safety_days_override) }}">
</label>

<label>
    <span>Safety stock multiplier</span>
    <input class="input input-bordered" type="number" step="0.0001" min="0" name="safety_stock_multiplier" value="{{ old('safety_stock_multiplier', $profile?->safety_stock_multiplier) }}">
</label>

<label>
    <span>Seasonality mode</span>
    <select class="select select-bordered" name="seasonality_mode">
        @forelse ($seasonalityModes as $mode)
            <option value="{{ $mode }}" @selected(old('seasonality_mode', $profile?->seasonality_mode ?? 'none') === $mode)>{{ $mode }}</option>
        @empty
            <option value="none">none</option>
        @endforelse
    </select>
</label>

<label>
    <span>Outlier multiplier</span>
    <input class="input input-bordered" type="number" step="0.0001" min="1" name="outlier_multiplier" value="{{ old('outlier_multiplier', $profile?->outlier_multiplier ?? '3.0000') }}">
</label>

<label>
    <span>Reservation strategy</span>
    <input class="input input-bordered" name="reservation_strategy" value="{{ old('reservation_strategy', $profile?->reservation_strategy ?? 'reserved_not_removed_from_free_stock') }}">
</label>

<label>
    <span>Pallet strategy</span>
    <input class="input input-bordered" name="pallet_strategy" value="{{ old('pallet_strategy', $profile?->pallet_strategy ?? 'show_only') }}">
</label>

<label>
    <span>Transport strategy</span>
    <input class="input input-bordered" name="transport_strategy" value="{{ old('transport_strategy', $profile?->transport_strategy ?? 'show_only') }}">
</label>

<label>
    <span>Notes</span>
    <textarea class="textarea textarea-bordered" name="notes">{{ old('notes', $profile?->notes) }}</textarea>
</label>

<label>
    <input type="hidden" name="seasonality_enabled" value="0">
    <input class="checkbox" type="checkbox" name="seasonality_enabled" value="1" @checked(old('seasonality_enabled', $profile?->seasonality_enabled ?? false))>
    <span>Enable seasonality</span>
</label>

<label>
    <input type="hidden" name="exclude_promotions" value="0">
    <input class="checkbox" type="checkbox" name="exclude_promotions" value="1" @checked(old('exclude_promotions', $profile?->exclude_promotions ?? true))>
    <span>Exclude promotions</span>
</label>

<label>
    <input type="hidden" name="exclude_anomalies" value="0">
    <input class="checkbox" type="checkbox" name="exclude_anomalies" value="1" @checked(old('exclude_anomalies', $profile?->exclude_anomalies ?? true))>
    <span>Exclude anomalies</span>
</label>

<label>
    <input type="hidden" name="outlier_detection_enabled" value="0">
    <input class="checkbox" type="checkbox" name="outlier_detection_enabled" value="1" @checked(old('outlier_detection_enabled', $profile?->outlier_detection_enabled ?? false))>
    <span>Detect outlier candidates</span>
</label>

<label>
    <input type="hidden" name="strategic_minimum_order_enabled" value="0">
    <input class="checkbox" type="checkbox" name="strategic_minimum_order_enabled" value="1" @checked(old('strategic_minimum_order_enabled', $profile?->strategic_minimum_order_enabled ?? false))>
    <span>Enable strategic minimum order</span>
</label>
