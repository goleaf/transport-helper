<section>
    <h2>T0/T1/T2/T3 timeline</h2>

    <div class="proposal-timeline" aria-label="T0 T1 T2 T3 timeline">
        <div class="proposal-timeline-node">
            <strong>T0</strong>
            <p>Today / order date</p>
            <p>{{ $item->t0_date?->toDateString() }}</p>
        </div>
        <div class="proposal-timeline-node">
            <strong>T1</strong>
            <p>Expected goods arrival</p>
            <p>{{ $item->t1_date?->toDateString() }}</p>
        </div>
        <div class="proposal-timeline-node">
            <strong>T2</strong>
            <p>End of planned coverage</p>
            <p>{{ $item->t2_date?->toDateString() }}</p>
        </div>
        <div class="proposal-timeline-node">
            <strong>T3</strong>
            <p>End of safety horizon</p>
            <p>{{ $item->t3_date?->toDateString() }}</p>
        </div>
    </div>

    <div class="proposal-periods">
        <div class="proposal-period">T0-T1: order execution period</div>
        <div class="proposal-period">T1-T2: planned coverage period</div>
        <div class="proposal-period">T2-T3: safety horizon</div>
    </div>

    <p><strong>Safety stock covers only T2-T3 and must not duplicate T1-T2.</strong></p>
</section>
