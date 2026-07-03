@extends('layouts.app')

@section('title')
Supply / Procurement Agent
@endsection

@section('content')
<header class="portal-hero">
    <div>
<p class="portal-eyebrow">Operations console</p>
<h1>Supply / Procurement Agent</h1>
<p>
    Imports, deterministic replenishment, approval queues, supplier email, AI extraction review,
    transport quotes and logistics in one operations console.
</p>
    </div>

    <nav class="hero-actions" aria-label="Primary actions">
<a class="button" href="{{ route('supply.dashboard') }}">Open Supply Dashboard</a>
<a class="button button-secondary" href="{{ route('supply.proposals.index') }}">Review Proposals</a>
    </nav>
</header>

<section aria-label="Project guardrails">
    <div class="guardrail-grid">
<article class="guardrail-card">
    <span>01</span>
    <h2>Deterministic replenishment</h2>
    <p>Order need is calculated by Laravel services only, with formula evidence and review flags.</p>
</article>

<article class="guardrail-card">
    <span>02</span>
    <h2>Manual review required</h2>
    <p>Quantities, supplier email sending, extraction acceptance, carrier choice and mismatches stay user-controlled.</p>
</article>

<article class="guardrail-card">
    <span>03</span>
    <h2>AI suggestions stay separate</h2>
    <p>AI may read and suggest from email, but it cannot mutate orders, logistics, quantities or carrier selection.</p>
</article>
    </div>
</section>

<section aria-label="Workflow entry points">
    <div class="section-heading">
<div>
    <p class="portal-eyebrow">Work queue</p>
    <h2>Start from the current workflow stage</h2>
</div>
<a href="{{ route('supply.dashboard') }}">Full dashboard</a>
    </div>

    <div class="workflow-grid">
<a class="workflow-card" href="{{ route('supply.imports.index') }}">
    <strong>Imports</strong>
    <span>Sales history, stock, inbound orders, reservations and product rules.</span>
</a>
<a class="workflow-card" href="{{ route('supply.calculations.index') }}">
    <strong>Calculations</strong>
    <span>T0/T1/T2/T3, trend, raw need, rounding and formula warnings.</span>
</a>
<a class="workflow-card" href="{{ route('supply.proposals.index') }}">
    <strong>Order proposals</strong>
    <span>Approve, adjust with reason, reject and convert to supplier orders.</span>
</a>
<a class="workflow-card" href="{{ route('supply.supplier-orders.index') }}">
    <strong>Supplier orders</strong>
    <span>Export files, prepare email drafts, approve and send safely.</span>
</a>
<a class="workflow-card" href="{{ route('supply.emails.index') }}">
    <strong>Inbound email</strong>
    <span>Store supplier replies, match supplier/order context and queue analysis.</span>
</a>
<a class="workflow-card" href="{{ route('supply.ai-extractions.index') }}">
    <strong>AI extractions</strong>
    <span>Accept, reject or keep needs-review without applying business changes.</span>
</a>
<a class="workflow-card" href="{{ route('supply.transport.quotes.index') }}">
    <strong>Transport quotes</strong>
    <span>Compare quote options while keeping carrier selection manual.</span>
</a>
<a class="workflow-card" href="{{ route('supply.logistics.index') }}">
    <strong>Logistics</strong>
    <span>Track ready dates, pickup, delivery, delays, receiving and records.</span>
</a>
    </div>
</section>

<section aria-label="Manual review points">
    <div class="section-heading">
<div>
    <p class="portal-eyebrow">Approval points</p>
    <h2>Do not automate these</h2>
</div>
<div class="mini-pills" aria-label="Control model">
    <span>Review</span>
    <span>Approve</span>
    <span>Audit</span>
</div>
    </div>

    <div class="approval-grid">
<a href="{{ route('supply.proposals.index') }}">
    <strong>Order quantity approval</strong>
    <span>Approve, adjust or reject with audit evidence.</span>
</a>
<a href="{{ route('supply.supplier-orders.index') }}">
    <strong>Supplier email sending</strong>
    <span>Drafts wait for explicit approval before send.</span>
</a>
<a href="{{ route('supply.ai-extractions.index') }}">
    <strong>AI extraction review</strong>
    <span>Accepted extraction remains separate until a later Laravel apply service.</span>
</a>
<a href="{{ route('supply.transport.quotes.index') }}">
    <strong>Carrier selection</strong>
    <span>Quotes can be compared, but the user chooses the carrier.</span>
</a>
    </div>
</section>

<section aria-label="Process map">
    <div class="section-heading">
<div>
    <p class="portal-eyebrow">Process map</p>
    <h2>Validated steps with visible history</h2>
</div>
<div class="mini-pills" aria-label="Calculation horizons">
    <span>T0</span>
    <span>T1</span>
    <span>T2</span>
    <span>T3</span>
</div>
    </div>

    <ol class="process-grid">
<li>
    <span>01</span>
    <strong>Import clean data</strong>
    <p>Rows keep raw and normalized values with validation errors.</p>
</li>
<li>
    <span>02</span>
    <strong>Calculate need</strong>
    <p>Formula output includes trend, safety stock, raw need and rounding.</p>
</li>
<li>
    <span>03</span>
    <strong>Resolve proposals</strong>
    <p>Only resolved, positive lines become supplier order items.</p>
</li>
<li>
    <span>04</span>
    <strong>Control downstream work</strong>
    <p>Email, confirmation, transport and logistics changes require review.</p>
</li>
    </ol>
</section>

<section aria-label="Administrative controls">
    <div class="section-heading">
<div>
    <p class="portal-eyebrow">Administration</p>
    <h2>Operational evidence and configuration</h2>
</div>
    </div>

    <div class="workflow-grid workflow-grid-compact">
<a class="workflow-card" href="{{ route('supply.audit-logs.index') }}">
    <strong>Audit Logs</strong>
    <span>Trace imports, approvals, email decisions, AI review and logistics changes.</span>
</a>
<a class="workflow-card" href="{{ route('supply.settings.index') }}">
    <strong>Settings</strong>
    <span>Control operational values without storing secrets in code.</span>
</a>
<a class="workflow-card" href="{{ route('supply.integrations.index') }}">
    <strong>Integrations</strong>
    <span>Keep external providers explicit, configured and approval-driven.</span>
</a>
    </div>
</section>
@endsection
