<div class="rounded-lg border border-gray-200 bg-white p-4">
    <h2 class="text-base font-semibold text-gray-900">Mappings</h2>
    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <form method="POST" action="{{ route('supply.pilots.mappings.import', $pilot) }}" class="space-y-3">
            @csrf
            <h3 class="font-medium text-gray-900">Import mapping</h3>
            <input name="import_type" value="{{ old('import_type', 'sales_history_sample') }}" class="input input-bordered input-primary w-full">
            <textarea name="mapping_json" rows="8" class="textarea textarea-bordered textarea-primary w-full font-mono text-sm">{{ old('mapping_json', $mappingTexts['import']) }}</textarea>
            <x-supply.button type="submit">Save import mapping</x-supply.button>
        </form>

        <form method="POST" action="{{ route('supply.pilots.mappings.manufacturer-form', $pilot) }}" class="space-y-3">
            @csrf
            <h3 class="font-medium text-gray-900">Manufacturer form mapping</h3>
            <textarea name="mapping_json" rows="10" class="textarea textarea-bordered textarea-primary w-full font-mono text-sm">{{ old('mapping_json', $mappingTexts['manufacturer_form']) }}</textarea>
            <x-supply.button type="submit">Save form mapping</x-supply.button>
        </form>

        <form method="POST" action="{{ route('supply.pilots.mappings.email', $pilot) }}" class="space-y-3">
            @csrf
            <h3 class="font-medium text-gray-900">Email sample mapping</h3>
            <input name="sample_type" value="{{ old('sample_type', 'supplier_confirmation') }}" class="input input-bordered input-primary w-full">
            <textarea name="mapping_json" rows="6" class="textarea textarea-bordered textarea-primary w-full font-mono text-sm">{{ old('mapping_json', $mappingTexts['email']) }}</textarea>
            <x-supply.button type="submit">Save email mapping</x-supply.button>
        </form>

        <form method="POST" action="{{ route('supply.pilots.mappings.carrier', $pilot) }}" class="space-y-3">
            @csrf
            <h3 class="font-medium text-gray-900">Carrier quote mapping</h3>
            <textarea name="mapping_json" rows="6" class="textarea textarea-bordered textarea-primary w-full font-mono text-sm">{{ old('mapping_json', $mappingTexts['carrier']) }}</textarea>
            <x-supply.button type="submit">Save carrier mapping</x-supply.button>
        </form>

        <form method="POST" action="{{ route('supply.pilots.mappings.logistics', $pilot) }}" class="space-y-3 lg:col-span-2">
            @csrf
            <h3 class="font-medium text-gray-900">Logistics mapping</h3>
            <textarea name="mapping_json" rows="4" class="textarea textarea-bordered textarea-primary w-full font-mono text-sm">{{ old('mapping_json', $mappingTexts['logistics']) }}</textarea>
            <x-supply.button type="submit">Save logistics mapping</x-supply.button>
        </form>
    </div>
</div>
