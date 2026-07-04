<nav class="tabs tabs-box">
    <a class="tab @class(['tab-active' => request()->routeIs('supply.master-data.dashboard')])" href="{{ route('supply.master-data.dashboard') }}">Dashboard</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.master-data.reports.*')])" href="{{ route('supply.master-data.reports.quality') }}">Quality</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.master-data.product-aliases.*')])" href="{{ route('supply.master-data.product-aliases.index') }}">Product aliases</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.master-data.supplier-aliases.*')])" href="{{ route('supply.master-data.supplier-aliases.index') }}">Supplier aliases</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.master-data.supplier-product-identities.*')])" href="{{ route('supply.master-data.supplier-product-identities.index') }}">Mappings</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.master-data.unknown-skus.*')])" href="{{ route('supply.master-data.unknown-skus.index') }}">Unknown SKUs</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.master-data.change-requests.*')])" href="{{ route('supply.master-data.change-requests.index') }}">Change requests</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.master-data.merge-proposals.*')])" href="{{ route('supply.master-data.merge-proposals.index') }}">Merges</a>
    <a class="tab @class(['tab-active' => request()->routeIs('supply.master-data.stewards.*')])" href="{{ route('supply.master-data.stewards.index') }}">Stewards</a>
</nav>
