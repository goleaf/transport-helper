<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Procurement controls</p>
            <h2>Budget and approval gate</h2>
        </div>
    </div>

    <x-supply.alert tone="info">These controls only check policy, budget and approval state. They do not approve, create supplier orders, send emails or select carriers.</x-supply.alert>

    <div class="grid">
        @forelse ($actions as $action)
            <form method="POST" action="{{ route('supply.procurement.gate') }}" class="form-grid">
                @csrf
                <input type="hidden" name="type" value="{{ $subjectType }}">
                <input type="hidden" name="id" value="{{ $subjectId }}">
                <input type="hidden" name="action" value="{{ $action['value'] }}">
                <div>
                    <strong>{{ $action['label'] }}</strong>
                    <p>{{ $action['description'] }}</p>
                </div>
                <div class="form-actions">
                    <x-supply.button type="submit" mode="outline">Run gate</x-supply.button>
                </div>
            </form>
        @empty
            <x-supply.empty-state title="No gate actions">No procurement gate actions are configured for this page.</x-supply.empty-state>
        @endforelse
    </div>

    <div class="grid">
        <form method="POST" action="{{ route('supply.procurement.approvals.request') }}" class="form-grid">
            @csrf
            <input type="hidden" name="approvable_type" value="{{ $subjectType }}">
            <input type="hidden" name="approvable_id" value="{{ $subjectId }}">
            <label>
                <span>Approval reason</span>
                <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
            </label>
            <div class="form-actions">
                <x-supply.button type="submit">Request approval</x-supply.button>
            </div>
        </form>

        <form method="POST" action="{{ route('supply.procurement.exceptions.store') }}" class="form-grid">
            @csrf
            <input type="hidden" name="exceptable_type" value="{{ $subjectType }}">
            <input type="hidden" name="exceptable_id" value="{{ $subjectId }}">
            <label>
                <span>Exception type</span>
                <select class="select select-bordered" name="exception_type" required>
                    <option value="budget_overrun">Budget overrun</option>
                    <option value="missing_price">Missing price</option>
                    <option value="supplier_minimum_not_met">Supplier minimum not met</option>
                    <option value="supplier_maximum_exceeded">Supplier maximum exceeded</option>
                    <option value="order_frequency_violation">Order frequency violation</option>
                    <option value="urgent_purchase">Urgent purchase</option>
                    <option value="manual_override">Manual override</option>
                    <option value="other">Other</option>
                </select>
            </label>
            <label>
                <span>Exception reason</span>
                <textarea class="textarea textarea-bordered" name="reason" required>{{ old('reason') }}</textarea>
            </label>
            <div class="form-actions">
                <x-supply.button type="submit" mode="outline">Request exception</x-supply.button>
            </div>
        </form>
    </div>
</section>
