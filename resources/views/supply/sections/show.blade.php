@extends('layouts.app')

@section('title')
{{ $section['label'] }}
@endsection

@section('content')
<header>
    <div>
        <p class="portal-eyebrow">Supply workspace</p>
        <h1>{{ $section['label'] }}</h1>
    </div>
</header>

<section>
    <div class="section-heading">
        <div>
            <p class="portal-eyebrow">Operational lane</p>
            <h2>{{ $section['label'] }}</h2>
        </div>
        <a href="{{ route('supply.dashboard') }}">Back to dashboard</a>
    </div>

    <dl>
        <dt>Registered route</dt>
        <dd>{{ route($section['route']) }}</dd>
        <dt>Navigation key</dt>
        <dd>{{ $section['key'] }}</dd>
        <dt>Control model</dt>
        <dd>Laravel data, human approval, audit-first workflow.</dd>
    </dl>
</section>

<section>
    <div class="guardrail-grid">
        <article class="card bg-base-100 border border-base-300 shadow-sm guardrail-card">
            <span>01</span>
            <h2>Human review</h2>
            <p>Workflow changes stay explicit and approval-driven.</p>
        </article>
        <article class="card bg-base-100 border border-base-300 shadow-sm guardrail-card">
            <span>02</span>
            <h2>Audit evidence</h2>
            <p>Operational decisions should leave traceable records.</p>
        </article>
        <article class="card bg-base-100 border border-base-300 shadow-sm guardrail-card">
            <span>03</span>
            <h2>Laravel authority</h2>
            <p>Business state is owned by Laravel services and Eloquent models.</p>
        </article>
    </div>
</section>
@endsection
