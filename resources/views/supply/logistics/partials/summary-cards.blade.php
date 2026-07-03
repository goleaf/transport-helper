<section>
    <h2>Summary</h2>
    <dl>
        @forelse ($summary as $label => $count)
            <dt>{{ str($label)->replace('_', ' ')->title() }}</dt>
            <dd>{{ $count }}</dd>
        @empty
            <dt>Records</dt>
            <dd>0</dd>
        @endforelse
    </dl>
</section>
