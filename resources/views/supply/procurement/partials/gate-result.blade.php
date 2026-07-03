<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Procurement gate</p>
            <h2><x-supply.human-label :value="$result['status'] ?? 'unknown'" /></h2>
        </div>
        <x-supply.status-badge :status="$result['status'] ?? 'unknown'" />
    </div>

    <dl class="structured-data">
        <dt>Action</dt>
        <dd><x-supply.human-label :value="$result['action'] ?? 'Not set'" /></dd>
        <dt>Mode</dt>
        <dd>{{ ucfirst($result['enforcement_mode'] ?? 'advisory') }}</dd>
        <dt>Estimated total</dt>
        <dd>{{ number_format((float) data_get($result, 'estimated_value.total', 0), 2) }} {{ data_get($result, 'estimated_value.currency', 'EUR') }}</dd>
        <dt>Budget status</dt>
        <dd>{{ ucfirst(data_get($result, 'budget_check.status', 'not checked')) }}</dd>
        <dt>Missing prices</dt>
        <dd>{{ data_get($result, 'estimated_value.missing_price_count', 0) }}</dd>
    </dl>

    @if (($result['blocking_reasons'] ?? []) !== [])
        <x-supply.alert tone="warning">
            <strong>Blocking reasons</strong>
            <ul>
                @forelse ($result['blocking_reasons'] as $reason)
                    <li><x-supply.human-label :value="$reason" /></li>
                @empty
                    <li>No blocking reasons.</li>
                @endforelse
            </ul>
        </x-supply.alert>
    @endif

    @if (($result['warnings'] ?? []) !== [])
        <x-supply.alert tone="info">
            <strong>Warnings</strong>
            <ul>
                @forelse ($result['warnings'] as $warning)
                    <li><x-supply.human-label :value="$warning" /></li>
                @empty
                    <li>No warnings.</li>
                @endforelse
            </ul>
        </x-supply.alert>
    @endif
</section>
