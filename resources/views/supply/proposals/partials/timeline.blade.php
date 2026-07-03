<section>
    <h2>T0/T1/T2/T3 timeline</h2>
    <style>
        .proposal-timeline { display: grid; grid-template-columns: repeat(4, minmax(120px, 1fr)); gap: 10px; margin: 14px 0; }
        .proposal-timeline-node { border: 1px solid #b8c2cc; padding: 12px; min-height: 96px; }
        .proposal-periods { display: grid; grid-template-columns: repeat(3, minmax(140px, 1fr)); gap: 10px; margin-top: 10px; }
        .proposal-period { background: #f3f6f9; padding: 10px; }
    </style>

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
