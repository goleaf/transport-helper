<section>
    <h2>Application Readiness</h2>
    <dl>
        <dt>Can apply later</dt>
        <dd>{{ $canApplyLabel }}</dd>
        <dt>Target action</dt>
        <dd>{{ $targetAction }}</dd>
        <dt>Blocking reasons</dt>
        <dd><x-supply.inline-list :items="$blockingReasons" /></dd>
    </dl>
</section>
