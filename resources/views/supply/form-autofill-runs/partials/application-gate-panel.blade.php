<section>
    <h2>Application Gate</h2>
    <p>This stage checks readiness only. It does not create supplier confirmations, carrier quotes or logistics updates.</p>
    <form method="post" action="{{ route('supply.form-autofill-runs.application-check', $run) }}">
        @csrf
        <input type="hidden" name="confirmation" value="1">
        <button type="submit">Check application readiness</button>
    </form>
</section>
