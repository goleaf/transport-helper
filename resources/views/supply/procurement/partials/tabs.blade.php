<nav class="tabs tabs-box">
    <a class="tab @class(['tab-active' => request()->routeIs('supply.procurement.reports.*')])" href="{{ route('supply.procurement.reports.index') }}">Reports</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.procurement.policies.*')])" href="{{ route('supply.procurement.policies.index') }}">Policies</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.procurement.budgets.*')])" href="{{ route('supply.procurement.budgets.index') }}">Budgets</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.procurement.prices.*')])" href="{{ route('supply.procurement.prices.index') }}">Prices</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.procurement.approvals.*')])" href="{{ route('supply.procurement.approvals.index') }}">Approvals</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.procurement.exceptions.*')])" href="{{ route('supply.procurement.exceptions.index') }}">Exceptions</a>
</nav>
